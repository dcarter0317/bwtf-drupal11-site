<?php

/**
 * @file
 * Drush php:script entry point for exporting production content.
 *
 * Example:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_export.php -- \
 *   --since=2026-01-01 --bundles=all --output=/home/bwtfcom/backups/bwtf-sync-20260731
 */

use Drupal\bwtf_content_sync\PackageExporter;
use Drupal\bwtf_content_sync\PackageUtils;

$options = PackageUtils::parseArguments(isset($extra) && is_array($extra) ? $extra : []);

$since_input = isset($options['since']) ? $options['since'] : '2026-01-01';
$since = is_numeric($since_input) ? (int) $since_input : strtotime($since_input . ' 00:00:00 UTC');
if (!$since) {
  throw new RuntimeException(sprintf('Unable to parse --since value: %s', $since_input));
}

// Default to every node bundle on the source site. Pass --bundles=page or a
// comma-separated list to narrow the export.
$bundles = isset($options['bundles'])
  ? array_values(array_filter(array_map('trim', explode(',', $options['bundles']))))
  : ['all'];
if (!$bundles) {
  throw new RuntimeException('At least one node bundle is required, or use --bundles=all.');
}

$output = isset($options['output'])
  ? $options['output']
  : sys_get_temp_dir() . '/bwtf-content-sync-' . gmdate('Ymd-His');

$exporter = new PackageExporter(
  \Drupal::service('entity_type.manager'),
  \Drupal::database(),
  \Drupal::service('file_system')
);

$manifest = $exporter->export($since, $bundles, $output, [
  'include_users' => !empty($options['include-users']),
  // Menu links are NOT exported by default. Production has a menu link for
  // many pages, and importing them repopulates the staging site's main
  // navigation on every run. The navigation is maintained on staging and
  // must be left alone. Pass --include-menu-links to override.
  'skip_menu_links' => empty($options['include-menu-links']),
  'skip_changed_terms' => !empty($options['skip-changed-terms']),
]);

print PHP_EOL;
print 'BWTF content package created.' . PHP_EOL;
print 'Package: ' . $output . PHP_EOL;
print 'Package ID: ' . $manifest['package_id'] . PHP_EOL;
print 'Bundle mode: ' . $manifest['bundle_mode'] . PHP_EOL;
print 'Bundles: ' . implode(', ', $manifest['node_bundles']) . PHP_EOL;
print 'Changed root nodes: ' . $manifest['root_node_count'] . PHP_EOL;
print 'Total entities, including dependencies: ' . $manifest['entity_count'] . PHP_EOL;
print 'Menu links: ' . (empty($options['include-menu-links']) ? 'excluded (navigation left untouched)' : 'INCLUDED') . PHP_EOL;
print 'Independently changed terms: ' . $manifest['changed_term_count'] . PHP_EOL;
print 'Aliases: ' . $manifest['alias_count'] . PHP_EOL;
print 'Source inventory rows: ' . $manifest['inventory_count'] . PHP_EOL;
print 'Warnings: ' . count($manifest['warnings']) . PHP_EOL;

if ($manifest['warnings']) {
  print PHP_EOL . 'Warnings:' . PHP_EOL;
  foreach ($manifest['warnings'] as $warning) {
    print ' - ' . $warning . PHP_EOL;
  }
}
