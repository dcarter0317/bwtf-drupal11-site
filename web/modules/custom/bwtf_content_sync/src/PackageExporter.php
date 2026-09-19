<?php

namespace Drupal\bwtf_content_sync;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Connection;

/**
 * Exports changed content and its referenced dependencies.
 */
class PackageExporter {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Exported records in dependency-first order.
   *
   * @var array
   */
  protected $records = [];

  /**
   * Exported aliases.
   *
   * @var array
   */
  protected $aliases = [];

  /**
   * Seen entity keys.
   *
   * @var array
   */
  protected $seen = [];

  /**
   * Export warnings.
   *
   * @var array
   */
  protected $warnings = [];

  /**
   * Package output directory.
   *
   * @var string
   */
  protected $outputDirectory;

  /**
   * Whether user entities may be exported.
   *
   * @var bool
   */
  protected $includeUsers = FALSE;

  /**
   * Number of independently changed taxonomy terms exported as roots.
   *
   * @var int
   */
  protected $changedTermCount = 0;

  /**
   * Constructs the exporter.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->fileSystem = $file_system;
  }

  /**
   * Exports changed nodes and referenced content entities.
   *
   * @param int $since
   *   Unix timestamp cutoff.
   * @param string[] $bundles
   *   Node bundles to export.
   * @param string $output_directory
   *   Destination package directory.
   * @param array $options
   *   Export options.
   *
   * @return array
   *   Package manifest.
   */
  public function export($since, array $bundles, $output_directory, array $options = []) {
    $this->records = [];
    $this->aliases = [];
    $this->seen = [];
    $this->warnings = [];
    $this->changedTermCount = 0;
    $this->outputDirectory = rtrim($output_directory, '/');
    $this->includeUsers = !empty($options['include_users']);

    PackageUtils::ensureDirectory($this->outputDirectory);
    PackageUtils::ensureDirectory($this->outputDirectory . '/files');

    $bundle_mode = $this->isAllBundles($bundles) ? 'all' : 'explicit';
    if ($bundle_mode === 'all') {
      $bundles = $this->discoverNodeBundles();
      if (!$bundles) {
        throw new \RuntimeException('No node bundles were found on the source site.');
      }
    }

    $node_ids = $this->findChangedNodeIds($since, $bundles);
    $node_storage = $this->entityTypeManager->getStorage('node');

    foreach ($node_ids as $node_id) {
      $node = $node_storage->load($node_id);
      if (!$node) {
        $this->warnings[] = sprintf('Node %s was selected but could not be loaded.', $node_id);
        continue;
      }

      $this->exportEntity($node);
      $this->exportAliasesForNode($node_id);
      if (empty($options['skip_menu_links'])) {
        $this->exportMenuLinksForNode($node_id);
      }
    }

    // Taxonomy terms referenced by the changed nodes are already pulled in as
    // dependencies. This additionally catches terms edited on their own -
    // renamed, re-described, or re-parented - whose nodes did not change.
    if (empty($options['skip_changed_terms'])) {
      $this->exportChangedTerms($since);
    }

    // The inventory lists every node that currently exists on the source site,
    // regardless of the cutoff. The importer uses it to report destination
    // nodes that no longer exist on the source. It never deletes anything.
    $inventory = $this->buildInventory($bundles);

    $package_id = 'bwtf-' . gmdate('Ymd-His') . '-' . substr(hash('sha256', implode(',', $node_ids)), 0, 12);
    $manifest = [
      'format' => 1,
      'package_id' => $package_id,
      'site_uuid' => $this->getSiteUuid(),
      'created_utc' => gmdate('c'),
      'source_drupal_version' => defined('Drupal::VERSION') ? \Drupal::VERSION : NULL,
      'since_timestamp' => (int) $since,
      'since_utc' => gmdate('c', $since),
      'bundle_mode' => $bundle_mode,
      'node_bundles' => array_values($bundles),
      'root_node_ids' => array_values(array_map('strval', $node_ids)),
      'root_node_count' => count($node_ids),
      'entity_count' => count($this->records),
      'alias_count' => count($this->aliases),
      'changed_term_count' => $this->changedTermCount,
      'inventory_count' => count($inventory),
      'include_users' => $this->includeUsers,
      'warnings' => $this->warnings,
    ];

    PackageUtils::writeJson($this->outputDirectory . '/entities.json', $this->records);
    PackageUtils::writeJson($this->outputDirectory . '/inventory.json', $inventory);
    PackageUtils::writeJson($this->outputDirectory . '/aliases.json', $this->aliases);
    PackageUtils::writeJson($this->outputDirectory . '/manifest.json', $manifest);

    return $manifest;
  }

  /**
   * Exports taxonomy terms changed since the cutoff, as export roots.
   *
   * Terms referenced by changed nodes are already exported as dependencies.
   * This covers the other case: a term edited on its own, whose nodes were
   * not touched and so would never be visited.
   *
   * @param int $since
   *   Unix timestamp cutoff.
   */
  protected function exportChangedTerms($since) {
    if (!$this->entityTypeManager->hasDefinition('taxonomy_term')) {
      return;
    }

    $table = 'taxonomy_term_field_data';
    try {
      if (!$this->database->schema()->tableExists($table)) {
        return;
      }
      // Term 'changed' tracking does not exist on every Drupal 8 minor
      // version, so fall back to exporting nothing rather than failing.
      if (!$this->database->schema()->fieldExists($table, 'changed')) {
        $this->warnings[] = 'The taxonomy term table has no changed column, so independently edited terms cannot be detected. Terms referenced by changed nodes were still exported.';
        return;
      }
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to inspect the taxonomy term schema: %s', $exception->getMessage());
      return;
    }

    try {
      $query = $this->database->select($table, 't');
      $query->addField('t', 'tid');
      $query->condition('t.changed', (int) $since, '>=');
      $query->condition('t.default_langcode', 1);
      $query->distinct();
      $ids = $query->execute()->fetchCol();
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to query changed taxonomy terms: %s', $exception->getMessage());
      return;
    }

    if (!$ids) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($storage->loadMultiple($ids) as $term) {
      if (!$term instanceof ContentEntityInterface) {
        continue;
      }
      $before = count($this->records);
      $this->exportEntity($term);
      if (count($this->records) > $before) {
        $this->changedTermCount++;
      }
    }
  }

  /**
   * Determines whether every node bundle was requested.
   *
   * @param string[] $bundles
   *   Requested bundles.
   *
   * @return bool
   *   TRUE when all bundles should be exported.
   */
  protected function isAllBundles(array $bundles) {
    if (!$bundles) {
      return TRUE;
    }

    foreach ($bundles as $bundle) {
      if (strtolower(trim((string) $bundle)) === 'all') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Discovers every node bundle that exists on the source site.
   *
   * @return string[]
   *   Node bundle machine names.
   */
  protected function discoverNodeBundles() {
    $bundles = [];

    try {
      $definitions = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
      $bundles = array_keys($definitions);
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to read node bundle info: %s', $exception->getMessage());
    }

    // Fall back to whatever the content table actually contains, and union it
    // in either way so a bundle with content is never missed.
    try {
      $query = $this->database->select('node_field_data', 'n');
      $query->addField('n', 'type');
      $query->distinct();
      $bundles = array_unique(array_merge($bundles, $query->execute()->fetchCol()));
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to read node types from the database: %s', $exception->getMessage());
    }

    $bundles = array_values(array_filter(array_map('strval', $bundles)));
    sort($bundles);

    return $bundles;
  }

  /**
   * Builds an inventory of every node currently on the source site.
   *
   * This is deliberately independent of the cutoff. The importer compares it
   * against destination nodes to report content that was deleted on the
   * source since the staging site was built.
   *
   * @param string[] $bundles
   *   Node bundles in scope.
   *
   * @return array
   *   Inventory rows.
   */
  protected function buildInventory(array $bundles) {
    $inventory = [];

    try {
      $query = $this->database->select('node_field_data', 'n');
      $query->innerJoin('node', 'nb', 'nb.nid = n.nid');
      $query->fields('n', ['nid', 'type', 'title', 'status', 'created', 'changed']);
      $query->addField('nb', 'uuid', 'uuid');
      $query->condition('n.default_langcode', 1);
      if ($bundles) {
        $query->condition('n.type', $bundles, 'IN');
      }
      $query->orderBy('n.nid', 'ASC');

      foreach ($query->execute() as $row) {
        $inventory[] = [
          'nid' => (string) $row->nid,
          'uuid' => (string) $row->uuid,
          'bundle' => (string) $row->type,
          'title' => (string) $row->title,
          'status' => (int) $row->status,
          'created' => (int) $row->created,
          'changed' => (int) $row->changed,
        ];
      }
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to build the source node inventory: %s', $exception->getMessage());
    }

    return $inventory;
  }

  /**
   * Finds changed or created node IDs.
   *
   * @param int $since
   *   Unix timestamp cutoff.
   * @param string[] $bundles
   *   Node bundles.
   *
   * @return array
   *   Node IDs.
   */
  protected function findChangedNodeIds($since, array $bundles) {
    $query = $this->database->select('node_field_data', 'n');
    $query->addField('n', 'nid');
    $query->condition('n.type', $bundles, 'IN');
    $changed = $query->orConditionGroup()
      ->condition('n.created', (int) $since, '>=')
      ->condition('n.changed', (int) $since, '>=');
    $query->condition($changed);
    $query->distinct();
    $query->orderBy('n.nid', 'ASC');

    return array_values($query->execute()->fetchCol());
  }

  /**
   * Exports an entity and its dependencies.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to export.
   * @param int|null $requested_revision_id
   *   Referenced source revision ID, when applicable.
   */
  protected function exportEntity(ContentEntityInterface $entity, $requested_revision_id = NULL) {
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = (string) $entity->id();
    $revision_id = method_exists($entity, 'getRevisionId') ? $entity->getRevisionId() : NULL;
    $key = $entity_type_id . ':' . $entity_id . ':' . (string) $revision_id;

    if (isset($this->seen[$key])) {
      return;
    }

    // Mark before recursion to prevent cycles, such as taxonomy parent loops.
    $this->seen[$key] = TRUE;

    $record = $this->buildRecord($entity, $requested_revision_id);

    foreach ($record['references'] as $reference) {
      if ($reference['entity_type'] === 'user' && !$this->includeUsers) {
        continue;
      }

      if (!$this->entityTypeManager->hasDefinition($reference['entity_type'])) {
        $this->warnings[] = sprintf(
          'Referenced entity type %s does not exist while exporting %s:%s.',
          $reference['entity_type'],
          $entity_type_id,
          $entity_id
        );
        continue;
      }

      $storage = $this->entityTypeManager->getStorage($reference['entity_type']);
      $target = NULL;
      if (!empty($reference['revision_id']) && method_exists($storage, 'loadRevision')) {
        $target = $storage->loadRevision($reference['revision_id']);
      }
      if (!$target) {
        $target = $storage->load($reference['id']);
      }

      if ($target instanceof ContentEntityInterface) {
        $this->exportEntity($target, isset($reference['revision_id']) ? $reference['revision_id'] : NULL);
      }
      else {
        $this->warnings[] = sprintf(
          'Unable to load dependency %s:%s referenced by %s:%s.',
          $reference['entity_type'],
          $reference['id'],
          $entity_type_id,
          $entity_id
        );
      }
    }

    if ($entity_type_id === 'file') {
      $this->exportFileBinary($entity, $record);
    }

    $record['hash'] = PackageUtils::hashData([
      'entity_type' => $record['entity_type'],
      'id' => $record['id'],
      'uuid' => $record['uuid'],
      'bundle' => $record['bundle'],
      'translations' => $record['translations'],
    ]);

    // Dependencies are appended first; parent entities follow.
    $this->records[] = $record;
  }

  /**
   * Builds a serializable entity record.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity.
   * @param int|null $requested_revision_id
   *   Revision requested by the parent reference.
   *
   * @return array
   *   Entity record.
   */
  protected function buildRecord(ContentEntityInterface $entity, $requested_revision_id = NULL) {
    $entity_type = $entity->getEntityType();
    $id_key = $entity_type->getKey('id');
    $uuid_key = $entity_type->getKey('uuid');
    $bundle_key = $entity_type->getKey('bundle');
    $langcode_key = $entity_type->getKey('langcode');
    $revision_key = $entity_type->getKey('revision');

    $skip_fields = array_filter([
      $id_key,
      $uuid_key,
      $bundle_key,
      $langcode_key,
      $revision_key,
      'revision_default',
      'revision_translation_affected',
    ]);

    $translations = [];
    $references = [];
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $field_values = [];

      foreach ($translation->getFields(FALSE) as $field_name => $field) {
        if (in_array($field_name, $skip_fields, TRUE)) {
          continue;
        }

        $definition = $field->getFieldDefinition();
        if ($this->shouldSkipField($definition)) {
          continue;
        }

        $values = $field->getValue();
        $field_values[$field_name] = $values;
        $this->collectReferences($definition, $values, $references);
      }

      $translations[$langcode] = $field_values;
    }

    $changed = 0;
    if (method_exists($entity, 'getChangedTime')) {
      $changed = (int) $entity->getChangedTime();
    }
    elseif ($entity->hasField('changed') && !$entity->get('changed')->isEmpty()) {
      $changed = (int) $entity->get('changed')->value;
    }

    $created = 0;
    if (method_exists($entity, 'getCreatedTime')) {
      $created = (int) $entity->getCreatedTime();
    }
    elseif ($entity->hasField('created') && !$entity->get('created')->isEmpty()) {
      $created = (int) $entity->get('created')->value;
    }

    return [
      'entity_type' => $entity->getEntityTypeId(),
      'id' => (string) $entity->id(),
      'uuid' => method_exists($entity, 'uuid') ? (string) $entity->uuid() : '',
      'bundle' => (string) $entity->bundle(),
      'default_langcode' => (string) $entity->language()->getId(),
      'revision_id' => method_exists($entity, 'getRevisionId') ? (string) $entity->getRevisionId() : NULL,
      'requested_revision_id' => $requested_revision_id !== NULL ? (string) $requested_revision_id : NULL,
      'created' => $created,
      'changed' => $changed,
      'translations' => $translations,
      'references' => array_values($references),
      'binary' => NULL,
    ];
  }

  /**
   * Determines whether a field should be skipped.
   */
  protected function shouldSkipField(FieldDefinitionInterface $definition) {
    if ($definition->isComputed()) {
      return TRUE;
    }
    if (method_exists($definition, 'isReadOnly') && $definition->isReadOnly()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Collects content-entity references from field values.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   Field definition.
   * @param array $values
   *   Raw field values.
   * @param array $references
   *   Reference accumulator.
   */
  protected function collectReferences(FieldDefinitionInterface $definition, array $values, array &$references) {
    $target_type = $definition->getSetting('target_type');
    if (!$target_type) {
      return;
    }

    foreach ($values as $item) {
      if (!isset($item['target_id']) || $item['target_id'] === NULL || $item['target_id'] === '') {
        continue;
      }

      // A target_id of 0 is not an entity. Taxonomy terms use it in the
      // parent field to mean "top level", and loading it always fails.
      if ((string) $item['target_id'] === '0') {
        continue;
      }

      $revision_id = isset($item['target_revision_id']) && $item['target_revision_id'] !== ''
        ? (string) $item['target_revision_id']
        : NULL;
      $reference_key = $target_type . ':' . (string) $item['target_id'] . ':' . (string) $revision_id;
      $references[$reference_key] = [
        'entity_type' => $target_type,
        'id' => (string) $item['target_id'],
        'revision_id' => $revision_id,
      ];
    }
  }

  /**
   * Copies a file entity's binary into the package.
   */
  protected function exportFileBinary(ContentEntityInterface $entity, array &$record) {
    if (!$entity->hasField('uri') || $entity->get('uri')->isEmpty()) {
      $this->warnings[] = sprintf('File entity %s has no URI.', $entity->id());
      return;
    }

    $uri = $entity->get('uri')->value;
    $package_relative = PackageUtils::uriToPackagePath($uri);
    $destination = $this->outputDirectory . '/' . $package_relative;
    PackageUtils::ensureDirectory(dirname($destination));

    $copied = FALSE;
    $realpath = $this->fileSystem->realpath($uri);
    if ($realpath && is_file($realpath)) {
      $copied = copy($realpath, $destination);
    }
    elseif (@is_file($uri)) {
      $copied = copy($uri, $destination);
    }
    else {
      $contents = @file_get_contents($uri);
      if ($contents !== FALSE) {
        $copied = file_put_contents($destination, $contents) !== FALSE;
      }
    }

    if ($copied) {
      $record['binary'] = [
        'uri' => $uri,
        'package_path' => $package_relative,
        'sha256' => hash_file('sha256', $destination),
        'size' => filesize($destination),
      ];
    }
    else {
      $this->warnings[] = sprintf('Unable to copy binary for file entity %s (%s).', $entity->id(), $uri);
    }
  }

  /**
   * Exports aliases for a node.
   */
  protected function exportAliasesForNode($node_id) {
    $path = '/node/' . $node_id;

    if ($this->entityTypeManager->hasDefinition('path_alias')) {
      try {
        $storage = $this->entityTypeManager->getStorage('path_alias');
        $aliases = $storage->loadByProperties(['path' => $path]);
        foreach ($aliases as $alias_entity) {
          $this->aliases[] = [
            'path' => method_exists($alias_entity, 'getPath') ? $alias_entity->getPath() : $alias_entity->get('path')->value,
            'alias' => method_exists($alias_entity, 'getAlias') ? $alias_entity->getAlias() : $alias_entity->get('alias')->value,
            'langcode' => $alias_entity->language()->getId(),
            'status' => method_exists($alias_entity, 'isPublished') ? (int) $alias_entity->isPublished() : 1,
          ];
        }
        return;
      }
      catch (\Exception $exception) {
        $this->warnings[] = sprintf('Unable to export path aliases for node %s through the entity API: %s', $node_id, $exception->getMessage());
      }
    }

    // Fallback for a Drupal 8 site that still has the legacy url_alias table.
    if ($this->database->schema()->tableExists('url_alias')) {
      $query = $this->database->select('url_alias', 'u')
        ->fields('u')
        ->condition('u.source', $path);
      foreach ($query->execute() as $row) {
        $this->aliases[] = [
          'path' => $row->source,
          'alias' => $row->alias,
          'langcode' => isset($row->langcode) ? $row->langcode : 'und',
          'status' => 1,
        ];
      }
    }
  }

  /**
   * Exports menu-link content entities pointing to a node.
   */
  protected function exportMenuLinksForNode($node_id) {
    if (!$this->entityTypeManager->hasDefinition('menu_link_content')) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('menu_link_content');
    $uris = [
      'entity:node/' . $node_id,
      'internal:/node/' . $node_id,
    ];

    $ids = [];
    try {
      $query = $storage->getQuery()->condition('link.uri', $uris, 'IN');
      if (method_exists($query, 'accessCheck')) {
        $query->accessCheck(FALSE);
      }
      $ids = $query->execute();
    }
    catch (\Exception $exception) {
      $this->warnings[] = sprintf('Unable to query menu links for node %s: %s', $node_id, $exception->getMessage());
      return;
    }

    foreach ($storage->loadMultiple($ids) as $menu_link) {
      if ($menu_link instanceof ContentEntityInterface) {
        $this->exportEntity($menu_link);
      }
    }
  }

  /**
   * Returns the source site's UUID when available.
   */
  protected function getSiteUuid() {
    try {
      return (string) \Drupal::config('system.site')->get('uuid');
    }
    catch (\Exception $exception) {
      return '';
    }
  }

}
