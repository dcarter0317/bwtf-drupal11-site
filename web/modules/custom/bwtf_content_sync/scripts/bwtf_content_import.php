<?php

/**
 * @file
 * Drush php:script entry point for importing a package on staging.
 *
 * Dry run:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_import.php -- \
 *   --package=/home/bwtfcom/backups/bwtf-sync-20260731
 *
 * Apply after reviewing the dry-run report:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_content_import.php -- \
 *   --package=/home/bwtfcom/backups/bwtf-sync-20260731 --apply
 */

use Drupal\bwtf_content_sync\PackageImporter;
use Drupal\bwtf_content_sync\PackageUtils;

$options = PackageUtils::parseArguments(isset($extra) && is_array($extra) ? $extra : []);
$package = isset($options['package']) ? rtrim($options['package'], '/') : NULL;
if (!$package) {
  throw new RuntimeException('The --package=/absolute/path option is required.');
}

$shared_options = [
  'overwrite_newer' => !empty($options['overwrite-newer']),
  'allow_site_uuid_mismatch' => !empty($options['allow-site-uuid-mismatch']),
  'skip_validation' => !empty($options['skip-validation']),
  'fallback_owner' => isset($options['fallback-owner']) ? $options['fallback-owner'] : NULL,
];

$make_importer = function () {
  return new PackageImporter(
    \Drupal::service('entity_type.manager'),
    \Drupal::database(),
    \Drupal::service('file_system')
  );
};

// Every apply run performs a complete preflight before writing anything.
$preflight_options = $shared_options + ['dry_run' => TRUE];
$preflight = $make_importer()->import($package, $preflight_options);
$preflight_path = $package . '/import-report-preflight-' . gmdate('Ymd-His') . '.json';
PackageUtils::writeJson($preflight_path, $preflight);

print PHP_EOL;
print 'BWTF content sync preflight complete.' . PHP_EOL;
print 'Report: ' . $preflight_path . PHP_EOL;
foreach ($preflight['summary'] as $status => $count) {
  print sprintf('  %-18s %d', $status . ':', $count) . PHP_EOL;
}

$blocking = $preflight['summary']['conflict'] + $preflight['summary']['error'] + $preflight['summary']['alias_conflict'];
if ($blocking > 0) {
  throw new RuntimeException(sprintf(
    'Preflight found %d blocking conflict/error result(s). Nothing was imported. Review the report.',
    $blocking
  ));
}

if (empty($options['apply'])) {
  print PHP_EOL . 'Dry run only. Add --apply after reviewing the report and taking a staging database backup.' . PHP_EOL;
  return;
}

$apply_options = $shared_options + ['dry_run' => FALSE];
$report = $make_importer()->import($package, $apply_options);
$report_path = $package . '/import-report-applied-' . gmdate('Ymd-His') . '.json';
PackageUtils::writeJson($report_path, $report);

print PHP_EOL;
print 'BWTF content package applied.' . PHP_EOL;
print 'Report: ' . $report_path . PHP_EOL;
foreach ($report['summary'] as $status => $count) {
  print sprintf('  %-18s %d', $status . ':', $count) . PHP_EOL;
}

$failures = $report['summary']['conflict'] + $report['summary']['error'] + $report['summary']['alias_conflict'];
if ($failures > 0) {
  throw new RuntimeException(sprintf(
    'The apply run completed with %d conflict/error result(s). Review the report and restore the backup if necessary.',
    $failures
  ));
}
