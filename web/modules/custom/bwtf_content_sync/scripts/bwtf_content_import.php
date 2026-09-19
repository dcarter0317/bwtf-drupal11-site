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

/**
 * Prints the report-only deletion section.
 */
$print_deletions = function (array $report) {
  $deletions = isset($report['deletions']) ? $report['deletions'] : [];
  if (!$deletions) {
    return;
  }

  print PHP_EOL . 'Deletion check (report only, nothing is ever deleted):' . PHP_EOL;
  if (empty($deletions['checked'])) {
    print '  Skipped. ' . (isset($deletions['reason']) ? $deletions['reason'] : '') . PHP_EOL;
    return;
  }

  print sprintf('  Source nodes: %d, destination nodes: %d', $deletions['source_node_count'], $deletions['destination_node_count']) . PHP_EOL;

  $missing = isset($deletions['missing_on_source']) ? $deletions['missing_on_source'] : [];
  if (!$missing) {
    print '  No destination nodes are missing from the source site.' . PHP_EOL;
  }
  else {
    print sprintf('  %d destination node(s) do not exist on the source site:', count($missing)) . PHP_EOL;
    $shown = 0;
    foreach ($missing as $row) {
      if ($shown++ >= 25) {
        print sprintf('    ... and %d more. See the JSON report.', count($missing) - 25) . PHP_EOL;
        break;
      }
      print sprintf(
        '    nid %-6s %-14s %-44s [%s]',
        $row['nid'],
        $row['bundle'],
        mb_substr($row['title'], 0, 44),
        $row['likely']
      ) . PHP_EOL;
    }
  }

  $mismatch = isset($deletions['id_mismatch']) ? $deletions['id_mismatch'] : [];
  if ($mismatch) {
    print sprintf('  %d node ID mismatch(es) worth reviewing:', count($mismatch)) . PHP_EOL;
    $shown = 0;
    foreach ($mismatch as $row) {
      if ($shown++ >= 25) {
        print sprintf('    ... and %d more. See the JSON report.', count($mismatch) - 25) . PHP_EOL;
        break;
      }
      print sprintf('    nid %-6s %-28s %s', $row['destination_nid'], $row['reason'], mb_substr($row['title'], 0, 44)) . PHP_EOL;
    }
  }
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

$print_deletions($preflight);

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

$print_deletions($report);

$failures = $report['summary']['conflict'] + $report['summary']['error'] + $report['summary']['alias_conflict'];
if ($failures > 0) {
  throw new RuntimeException(sprintf(
    'The apply run completed with %d conflict/error result(s). Review the report and restore the backup if necessary.',
    $failures
  ));
}
