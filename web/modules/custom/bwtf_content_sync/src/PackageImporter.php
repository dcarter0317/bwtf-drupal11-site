<?php

namespace Drupal\bwtf_content_sync;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Connection;

/**
 * Imports a BWTF content package into Drupal 11 staging.
 */
class PackageImporter {

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
   * Source-to-destination entity ID map.
   *
   * @var array
   */
  protected $idMap = [];

  /**
   * Source-to-destination revision ID map.
   *
   * @var array
   */
  protected $revisionMap = [];

  /**
   * Package directory.
   *
   * @var string
   */
  protected $packageDirectory;

  /**
   * Import options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Constructs the importer.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->fileSystem = $file_system;
  }

  /**
   * Imports or analyzes a package.
   *
   * @param string $package_directory
   *   Package directory.
   * @param array $options
   *   Import options.
   *
   * @return array
   *   Import report.
   */
  public function import($package_directory, array $options = []) {
    $this->packageDirectory = rtrim($package_directory, '/');
    $this->options = $options + [
      'dry_run' => TRUE,
      'overwrite_newer' => FALSE,
      'allow_site_uuid_mismatch' => FALSE,
      'skip_validation' => FALSE,
      'fallback_owner' => NULL,
    ];
    $this->idMap = [];
    $this->revisionMap = [];

    $manifest = PackageUtils::readJson($this->packageDirectory . '/manifest.json');
    $records = PackageUtils::readJson($this->packageDirectory . '/entities.json');
    $aliases = PackageUtils::readJson($this->packageDirectory . '/aliases.json');

    $this->validateManifest($manifest);

    $report = [
      'package_id' => isset($manifest['package_id']) ? $manifest['package_id'] : '',
      'dry_run' => (bool) $this->options['dry_run'],
      'started_utc' => gmdate('c'),
      'source_site_uuid' => isset($manifest['site_uuid']) ? $manifest['site_uuid'] : '',
      'destination_site_uuid' => $this->getSiteUuid(),
      'summary' => [
        'create' => 0,
        'update' => 0,
        'unchanged' => 0,
        'conflict' => 0,
        'error' => 0,
        'alias_create' => 0,
        'alias_update' => 0,
        'alias_unchanged' => 0,
        'alias_conflict' => 0,
      ],
      'entities' => [],
      'aliases' => [],
    ];

    foreach ($records as $record) {
      $result = $this->processRecord($record, $manifest);
      $report['entities'][] = $result;
      $status = isset($result['status']) ? $result['status'] : 'error';
      if (isset($report['summary'][$status])) {
        $report['summary'][$status]++;
      }
      else {
        $report['summary']['error']++;
      }
    }

    foreach ($aliases as $alias) {
      $result = $this->processAlias($alias);
      $report['aliases'][] = $result;
      $key = 'alias_' . $result['status'];
      if (isset($report['summary'][$key])) {
        $report['summary'][$key]++;
      }
      else {
        $report['summary']['alias_conflict']++;
      }
    }

    $report['finished_utc'] = gmdate('c');
    return $report;
  }

  /**
   * Validates the package manifest.
   */
  protected function validateManifest(array $manifest) {
    if (!isset($manifest['format']) || (int) $manifest['format'] !== 1) {
      throw new \RuntimeException('Unsupported or missing BWTF package format.');
    }

    $source_uuid = isset($manifest['site_uuid']) ? (string) $manifest['site_uuid'] : '';
    $destination_uuid = $this->getSiteUuid();
    if (!$this->options['allow_site_uuid_mismatch'] && $source_uuid && $destination_uuid && $source_uuid !== $destination_uuid) {
      throw new \RuntimeException(sprintf(
        'Site UUID mismatch. Source is %s and destination is %s. This package may belong to another site.',
        $source_uuid,
        $destination_uuid
      ));
    }
  }

  /**
   * Processes one entity record.
   */
  protected function processRecord(array $record, array $manifest) {
    $entity_type_id = isset($record['entity_type']) ? $record['entity_type'] : '';
    $source_id = isset($record['id']) ? (string) $record['id'] : '';
    $source_uuid = isset($record['uuid']) ? (string) $record['uuid'] : '';
    $result = [
      'entity_type' => $entity_type_id,
      'source_id' => $source_id,
      'source_uuid' => $source_uuid,
      'bundle' => isset($record['bundle']) ? $record['bundle'] : '',
      'status' => 'error',
      'destination_id' => NULL,
      'message' => '',
    ];

    if (!$entity_type_id || !$source_id) {
      $result['message'] = 'Record is missing entity_type or id.';
      return $result;
    }

    if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
      $result['message'] = sprintf('Destination does not define entity type %s.', $entity_type_id);
      return $result;
    }

    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $resolution = $this->resolveDestinationEntity($storage, $record);
      if ($resolution['conflict']) {
        $result['status'] = 'conflict';
        $result['message'] = $resolution['message'];
        return $result;
      }

      $entity = $resolution['entity'];
      $is_new = $entity === NULL;
      if (!$is_new) {
        $result['destination_id'] = (string) $entity->id();
        $destination_hash = $this->buildDestinationHash($entity, $record);
        if (!empty($record['hash']) && hash_equals($record['hash'], $destination_hash)) {
          $this->registerMaps($record, $entity);
          $result['status'] = 'unchanged';
          $result['message'] = 'Destination already matches the package.';
          return $result;
        }

        $destination_changed = $this->getChangedTime($entity);
        $source_changed = isset($record['changed']) ? (int) $record['changed'] : 0;
        if (!$this->options['overwrite_newer'] && $source_changed && $destination_changed > ($source_changed + 60)) {
          $result['status'] = 'conflict';
          $result['message'] = sprintf(
            'Destination was changed later than production (%s > %s). Review before using --overwrite-newer.',
            gmdate('c', $destination_changed),
            gmdate('c', $source_changed)
          );
          return $result;
        }
      }

      // Structural preflight: verify the destination bundle, fields,
      // references, and packaged binary before classifying the operation.
      $preview = $is_new ? $this->createEntity($storage, $record) : clone $entity;
      $this->assertRecordCompatible($preview, $record);
      $this->validateBinaryRecord($record);

      if ($this->options['dry_run']) {
        $predicted_id = $is_new ? $source_id : (string) $entity->id();
        $this->idMap[$entity_type_id . ':' . $source_id] = $predicted_id;
        if (!empty($record['revision_id'])) {
          $this->revisionMap[$entity_type_id . ':' . $source_id . ':' . $record['revision_id']] = $record['revision_id'];
        }
        $result['status'] = $is_new ? 'create' : 'update';
        $result['destination_id'] = $predicted_id;
        $result['message'] = $is_new ? 'Would create entity.' : 'Would update entity.';
        return $result;
      }

      if ($is_new) {
        $entity = $preview;
      }

      $this->copyBinary($record);
      $this->applyRecordToEntity($entity, $record, !$is_new);
      $entity->save();
      $this->registerMaps($record, $entity);
      $this->writeImportLog($record, $entity, $manifest, $is_new ? 'created' : 'updated');

      $result['status'] = $is_new ? 'create' : 'update';
      $result['destination_id'] = (string) $entity->id();
      $result['message'] = $is_new ? 'Entity created.' : 'Entity updated.';
    }
    catch (\Exception $exception) {
      $result['status'] = 'error';
      $result['message'] = $exception->getMessage();
    }

    return $result;
  }

  /**
   * Resolves an entity by UUID first and then by source ID.
   */
  protected function resolveDestinationEntity($storage, array $record) {
    $source_id = (string) $record['id'];
    $source_uuid = isset($record['uuid']) ? (string) $record['uuid'] : '';
    $by_uuid = NULL;
    $by_id = $storage->load($source_id);

    if ($source_uuid) {
      $matches = $storage->loadByProperties(['uuid' => $source_uuid]);
      if ($matches) {
        $by_uuid = reset($matches);
      }
    }

    if ($by_uuid && $by_id && (string) $by_uuid->id() !== (string) $by_id->id()) {
      return [
        'entity' => NULL,
        'conflict' => TRUE,
        'message' => sprintf(
          'UUID %s exists as destination ID %s, while source ID %s is occupied by another entity.',
          $source_uuid,
          $by_uuid->id(),
          $source_id
        ),
      ];
    }

    if (!$by_uuid && $by_id) {
      $destination_uuid = method_exists($by_id, 'uuid') ? (string) $by_id->uuid() : '';
      if ($source_uuid && $destination_uuid && $source_uuid !== $destination_uuid) {
        return [
          'entity' => NULL,
          'conflict' => TRUE,
          'message' => sprintf(
            'Source ID %s is already occupied by a different UUID (%s). Resolve the staging ID collision before importing.',
            $source_id,
            $destination_uuid
          ),
        ];
      }
    }

    return [
      'entity' => $by_uuid ?: $by_id,
      'conflict' => FALSE,
      'message' => '',
    ];
  }

  /**
   * Creates an empty entity with source identity values.
   */
  protected function createEntity($storage, array $record) {
    $entity_type = $storage->getEntityType();
    $values = [];
    $id_key = $entity_type->getKey('id');
    $uuid_key = $entity_type->getKey('uuid');
    $bundle_key = $entity_type->getKey('bundle');
    $langcode_key = $entity_type->getKey('langcode');

    if ($id_key) {
      $values[$id_key] = $record['id'];
    }
    if ($uuid_key && !empty($record['uuid'])) {
      $values[$uuid_key] = $record['uuid'];
    }
    if ($bundle_key && isset($record['bundle']) && $record['bundle'] !== '') {
      $values[$bundle_key] = $record['bundle'];
    }
    if ($langcode_key && !empty($record['default_langcode'])) {
      $values[$langcode_key] = $record['default_langcode'];
    }

    return $storage->create($values);
  }

  /**
   * Performs a non-writing compatibility check for an entity record.
   *
   * This intentionally avoids full entity validation because dependencies in a
   * dry run have not been saved yet. Full validation occurs during apply.
   */
  protected function assertRecordCompatible(ContentEntityInterface $entity, array $record) {
    foreach ($record['translations'] as $langcode => $field_values) {
      foreach ($field_values as $field_name => $values) {
        if (!$entity->hasField($field_name)) {
          throw new \RuntimeException(sprintf(
            'Destination %s bundle %s does not have source field %s.',
            $record['entity_type'],
            isset($record['bundle']) ? $record['bundle'] : '',
            $field_name
          ));
        }

        $definition = $entity->getFieldDefinition($field_name);
        if ($this->shouldSkipField($definition)) {
          continue;
        }

        $target_type = $definition->getSetting('target_type');
        if (!$target_type || !$this->entityTypeManager->hasDefinition($target_type)) {
          continue;
        }

        $target_storage = $this->entityTypeManager->getStorage($target_type);
        foreach ($values as $item) {
          if (!isset($item['target_id']) || $item['target_id'] === '' || $item['target_id'] === NULL) {
            continue;
          }

          $source_target_id = (string) $item['target_id'];
          $map_key = $target_type . ':' . $source_target_id;
          if (isset($this->idMap[$map_key])) {
            continue;
          }

          if ($target_storage->load($source_target_id)) {
            continue;
          }

          if ($target_type === 'user' && $this->options['fallback_owner'] !== NULL) {
            if ($target_storage->load($this->options['fallback_owner'])) {
              continue;
            }
          }

          throw new \RuntimeException(sprintf(
            'Referenced dependency %s:%s is not present in the package or destination.',
            $target_type,
            $source_target_id
          ));
        }
      }
    }
  }

  /**
   * Verifies a packaged binary without writing it.
   */
  protected function validateBinaryRecord(array $record) {
    if (empty($record['binary']['package_path'])) {
      return;
    }

    $source = $this->packageDirectory . '/' . $record['binary']['package_path'];
    if (!is_file($source)) {
      throw new \RuntimeException(sprintf('Packaged file is missing: %s', $source));
    }

    if (!empty($record['binary']['sha256']) && !hash_equals($record['binary']['sha256'], hash_file('sha256', $source))) {
      throw new \RuntimeException(sprintf('Packaged file checksum failed: %s', $source));
    }
  }

  /**
   * Applies field values and synchronization metadata.
   */
  protected function applyRecordToEntity(ContentEntityInterface $entity, array $record, $is_update) {
    if ($is_update && method_exists($entity, 'setNewRevision')) {
      $entity->setNewRevision(TRUE);
      if (method_exists($entity, 'setRevisionLogMessage')) {
        $entity->setRevisionLogMessage('Synchronized from Drupal 8 production by BWTF Content Sync.');
      }
    }

    $default_langcode = !empty($record['default_langcode']) ? $record['default_langcode'] : $entity->language()->getId();
    foreach ($record['translations'] as $langcode => $field_values) {
      if ($langcode === $default_langcode || $langcode === $entity->language()->getId()) {
        $translation = $entity->getUntranslated();
      }
      elseif ($entity->hasTranslation($langcode)) {
        $translation = $entity->getTranslation($langcode);
      }
      else {
        $translation = $entity->addTranslation($langcode, []);
      }

      $this->applyFieldValues($translation, $field_values);
    }

    if (method_exists($entity, 'setCreatedTime') && !empty($record['created'])) {
      $entity->setCreatedTime((int) $record['created']);
    }
    if (method_exists($entity, 'setChangedTime') && !empty($record['changed'])) {
      $entity->setChangedTime((int) $record['changed']);
    }
    if (method_exists($entity, 'setRevisionCreationTime') && !empty($record['changed'])) {
      $entity->setRevisionCreationTime((int) $record['changed']);
    }
    if (method_exists($entity, 'setSyncing')) {
      $entity->setSyncing(TRUE);
    }

    if (!$this->options['skip_validation']) {
      $violations = $entity->validate();
      if ($violations->count()) {
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }
        throw new \RuntimeException('Entity validation failed: ' . implode('; ', $messages));
      }
    }
  }

  /**
   * Applies writable field values to an entity translation.
   */
  protected function applyFieldValues(ContentEntityInterface $entity, array $field_values) {
    foreach ($field_values as $field_name => $values) {
      if (!$entity->hasField($field_name)) {
        throw new \RuntimeException(sprintf(
          'Destination %s:%s does not have source field %s.',
          $entity->getEntityTypeId(),
          $entity->id() ?: 'new',
          $field_name
        ));
      }

      $definition = $entity->getFieldDefinition($field_name);
      if ($this->shouldSkipField($definition)) {
        continue;
      }

      $values = $this->rewriteReferences($definition, $values);
      $entity->set($field_name, $values);
    }
  }

  /**
   * Rewrites source entity and revision IDs using imported dependency maps.
   */
  protected function rewriteReferences(FieldDefinitionInterface $definition, array $values) {
    $target_type = $definition->getSetting('target_type');
    if (!$target_type) {
      return $values;
    }

    foreach ($values as &$item) {
      if (!isset($item['target_id']) || $item['target_id'] === '') {
        continue;
      }

      $source_target_id = (string) $item['target_id'];
      $map_key = $target_type . ':' . $source_target_id;
      if (isset($this->idMap[$map_key])) {
        $item['target_id'] = $this->idMap[$map_key];
      }
      elseif ($target_type === 'user' && $this->options['fallback_owner'] !== NULL) {
        $storage = $this->entityTypeManager->getStorage('user');
        if (!$storage->load($source_target_id)) {
          $item['target_id'] = (string) $this->options['fallback_owner'];
        }
      }

      if (!empty($item['target_revision_id'])) {
        $revision_key = $target_type . ':' . $source_target_id . ':' . (string) $item['target_revision_id'];
        if (isset($this->revisionMap[$revision_key])) {
          $item['target_revision_id'] = $this->revisionMap[$revision_key];
        }
      }
    }
    unset($item);

    return $values;
  }

  /**
   * Determines whether a field is computed or read-only.
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
   * Builds a hash of destination values corresponding to a source record.
   */
  protected function buildDestinationHash(ContentEntityInterface $entity, array $record) {
    $translations = [];
    foreach ($record['translations'] as $langcode => $source_fields) {
      if (!$entity->hasTranslation($langcode)) {
        $translations[$langcode] = ['__missing_translation' => TRUE];
        continue;
      }

      $translation = $entity->getTranslation($langcode);
      $values = [];
      foreach ($source_fields as $field_name => $unused) {
        if (!$translation->hasField($field_name)) {
          $values[$field_name] = ['__missing_field' => TRUE];
        }
        else {
          $values[$field_name] = $translation->get($field_name)->getValue();
        }
      }
      $translations[$langcode] = $values;
    }

    return PackageUtils::hashData([
      'entity_type' => $record['entity_type'],
      'id' => $record['id'],
      'uuid' => $record['uuid'],
      'bundle' => $record['bundle'],
      'translations' => $translations,
    ]);
  }

  /**
   * Copies a packaged file binary to its stream-wrapper URI.
   */
  protected function copyBinary(array $record) {
    if (empty($record['binary']['package_path']) || empty($record['binary']['uri'])) {
      return;
    }

    $source = $this->packageDirectory . '/' . $record['binary']['package_path'];
    $destination_uri = $record['binary']['uri'];
    if (!is_file($source)) {
      throw new \RuntimeException(sprintf('Packaged file is missing: %s', $source));
    }

    if (!empty($record['binary']['sha256']) && !hash_equals($record['binary']['sha256'], hash_file('sha256', $source))) {
      throw new \RuntimeException(sprintf('Packaged file checksum failed: %s', $source));
    }

    $destination_directory = dirname($destination_uri);
    $flags = FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS;
    if (!$this->fileSystem->prepareDirectory($destination_directory, $flags)) {
      throw new \RuntimeException(sprintf('Unable to prepare destination directory: %s', $destination_directory));
    }

    $contents = file_get_contents($source);
    if ($contents === FALSE || file_put_contents($destination_uri, $contents) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to copy %s to %s.', $source, $destination_uri));
    }
  }

  /**
   * Registers imported entity and revision mappings.
   */
  protected function registerMaps(array $record, ContentEntityInterface $entity) {
    $entity_type_id = $record['entity_type'];
    $source_id = (string) $record['id'];
    $this->idMap[$entity_type_id . ':' . $source_id] = (string) $entity->id();

    if (!empty($record['revision_id']) && method_exists($entity, 'getRevisionId')) {
      $this->revisionMap[
        $entity_type_id . ':' . $source_id . ':' . (string) $record['revision_id']
      ] = (string) $entity->getRevisionId();
    }
  }

  /**
   * Imports or updates a path alias.
   */
  protected function processAlias(array $alias) {
    $result = [
      'path' => isset($alias['path']) ? $alias['path'] : '',
      'alias' => isset($alias['alias']) ? $alias['alias'] : '',
      'langcode' => isset($alias['langcode']) ? $alias['langcode'] : 'und',
      'status' => 'conflict',
      'message' => '',
    ];

    if (!$this->entityTypeManager->hasDefinition('path_alias')) {
      $result['message'] = 'Destination does not define the path_alias entity type.';
      return $result;
    }

    $path = $this->rewriteInternalPath($result['path']);
    $result['path'] = $path;
    $storage = $this->entityTypeManager->getStorage('path_alias');
    $existing_by_path = $storage->loadByProperties([
      'path' => $path,
      'langcode' => $result['langcode'],
    ]);
    $existing = $existing_by_path ? reset($existing_by_path) : NULL;

    $alias_collisions = $storage->loadByProperties([
      'alias' => $result['alias'],
      'langcode' => $result['langcode'],
    ]);
    foreach ($alias_collisions as $collision) {
      if ($collision->get('path')->value !== $path) {
        $result['message'] = sprintf(
          'Alias %s is already assigned to %s.',
          $result['alias'],
          $collision->get('path')->value
        );
        return $result;
      }
    }

    if ($existing && $existing->get('alias')->value === $result['alias']) {
      $result['status'] = 'unchanged';
      $result['message'] = 'Alias already matches.';
      return $result;
    }

    if ($this->options['dry_run']) {
      $result['status'] = $existing ? 'update' : 'create';
      $result['message'] = $existing ? 'Would update alias.' : 'Would create alias.';
      return $result;
    }

    if (!$existing) {
      $existing = $storage->create([
        'path' => $path,
        'alias' => $result['alias'],
        'langcode' => $result['langcode'],
        'status' => isset($alias['status']) ? (int) $alias['status'] : 1,
      ]);
    }
    else {
      $existing->set('alias', $result['alias']);
      if ($existing->hasField('status')) {
        $existing->set('status', isset($alias['status']) ? (int) $alias['status'] : 1);
      }
    }
    if (method_exists($existing, 'setSyncing')) {
      $existing->setSyncing(TRUE);
    }
    $existing->save();

    $result['status'] = $existing_by_path ? 'update' : 'create';
    $result['message'] = $result['status'] === 'update' ? 'Alias updated.' : 'Alias created.';
    return $result;
  }

  /**
   * Rewrites /node/N paths when an ID was remapped.
   */
  protected function rewriteInternalPath($path) {
    if (preg_match('#^/node/(\d+)$#', $path, $matches)) {
      $key = 'node:' . $matches[1];
      if (isset($this->idMap[$key])) {
        return '/node/' . $this->idMap[$key];
      }
    }
    return $path;
  }

  /**
   * Returns an entity's changed timestamp.
   */
  protected function getChangedTime(ContentEntityInterface $entity) {
    if (method_exists($entity, 'getChangedTime')) {
      return (int) $entity->getChangedTime();
    }
    if ($entity->hasField('changed') && !$entity->get('changed')->isEmpty()) {
      return (int) $entity->get('changed')->value;
    }
    return 0;
  }

  /**
   * Writes an import log row.
   */
  protected function writeImportLog(array $record, ContentEntityInterface $entity, array $manifest, $status) {
    if (!$this->database->schema()->tableExists('bwtf_content_sync_log')) {
      return;
    }

    $this->database->merge('bwtf_content_sync_log')
      ->key([
        'entity_type' => $record['entity_type'],
        'source_id' => (string) $record['id'],
      ])
      ->fields([
        'source_uuid' => isset($record['uuid']) ? (string) $record['uuid'] : '',
        'destination_id' => (string) $entity->id(),
        'source_changed' => isset($record['changed']) ? (int) $record['changed'] : 0,
        'source_hash' => isset($record['hash']) ? (string) $record['hash'] : '',
        'package_id' => isset($manifest['package_id']) ? (string) $manifest['package_id'] : '',
        'imported' => time(),
        'status' => $status,
      ])
      ->execute();
  }

  /**
   * Returns destination site's UUID.
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
