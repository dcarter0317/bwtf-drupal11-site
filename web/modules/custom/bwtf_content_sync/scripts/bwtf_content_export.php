<?php

/**
 * @file
 * Drush php:script entry point for exporting production content.
 *
 * Example:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_export.php -- \
 *   --since=2026-01-01 --bundles=page --output=/home/bwtfcom/backups/bwtf-sync-20260731
 */

use Drupal\bwtf_content_sync\PackageExporter;
use Drupal\bwtf_content_sync\PackageUtils;

$options = PackageUtils::parseArguments(isset($extra) && is_array($extra) ? $extra : []);

$since_input = isset($options['since']) ? $options['since'] : '2026-01-01';
$since = is_numeric($since_input) ? (int) $since_input : strtotime($since_input . ' 00:00:00 UTC');
if (!$since) {
  throw new RuntimeException(sprintf('Unable to parse --since value: %s', $since_input));
}

$bundles = isset($options['bundles']) ? array_filter(array_map('trim', explode(',', $options['bundles']))) : ['page'];
if (!$bundles) {
  throw new RuntimeException('At least one node bundle is required.');
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
  'skip_menu_links' => !empty($options['skip-menu-links']),
]);

print PHP_EOL;
print 'BWTF content package created.' . PHP_EOL;
print 'Package: ' . $output . PHP_EOL;
print 'Package ID: ' . $manifest['package_id'] . PHP_EOL;
print 'Changed root nodes: ' . $manifest['root_node_count'] . PHP_EOL;
print 'Total entities, including dependencies: ' . $manifest['entity_count'] . PHP_EOL;
print 'Aliases: ' . $manifest['alias_count'] . PHP_EOL;
print 'Warnings: ' . count($manifest['warnings']) . PHP_EOL;

if ($manifest['warnings']) {
  print PHP_EOL . 'Warnings:' . PHP_EOL;
  foreach ($manifest['warnings'] as $warning) {
    print ' - ' . $warning . PHP_EOL;
  }
}
