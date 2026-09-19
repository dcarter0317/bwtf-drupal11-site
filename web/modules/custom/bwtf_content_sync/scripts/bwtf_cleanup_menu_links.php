<?php

/**
 * @file
 * Removes menu links that BWTF Content Sync imported from production.
 *
 * Earlier package versions exported a menu link for every changed node, which
 * repopulated the staging site's navigation on each import. The navigation is
 * maintained on staging and should never be touched by the sync.
 *
 * This only ever considers menu links recorded in bwtf_content_sync_log, so
 * links created by hand on staging are never affected.
 *
 * Dry run (default), lists what it would act on:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_cleanup_menu_links.php
 *
 * Disable them, keeping the entities:
 * ... -- --disable --apply
 *
 * Delete them:
 * ... -- --delete --apply
 *
 * Restrict to one menu:
 * ... -- --menu=main --delete --apply
 */

use Drupal\bwtf_content_sync\PackageUtils;

$options = PackageUtils::parseArguments(isset($extra) && is_array($extra) ? $extra : []);

$apply = !empty($options['apply']);
$delete = !empty($options['delete']);
$disable = !empty($options['disable']);
$menu_filter = isset($options['menu']) ? trim((string) $options['menu']) : '';
$protect_file = isset($options['protect-uuids']) ? trim((string) $options['protect-uuids']) : '';

// Links that existed before the first sync are the site's own navigation and
// must never be touched. Both sites descend from one database, so the
// importer matched and updated existing nav links rather than creating them,
// and the sync log cannot tell the two apart. The pre-sync database dump can.
$protected = [];
if ($protect_file !== '') {
  if (!is_file($protect_file)) {
    throw new RuntimeException(sprintf('Protect list not found: %s', $protect_file));
  }
  foreach (file($protect_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line !== '') {
      $protected[strtolower($line)] = TRUE;
    }
  }
  if (!$protected) {
    throw new RuntimeException('The protect list is empty. Refusing to run: that would treat the entire navigation as removable.');
  }
}
elseif ($apply) {
  throw new RuntimeException('--apply requires --protect-uuids=/path/to/list so pre-existing navigation is preserved. Build it from the pre-sync database dump.');
}

if ($delete && $disable) {
  throw new RuntimeException('Choose --delete or --disable, not both.');
}
if ($apply && !$delete && !$disable) {
  throw new RuntimeException('--apply needs either --delete or --disable.');
}

$database = \Drupal::database();
if (!$database->schema()->tableExists('bwtf_content_sync_log')) {
  throw new RuntimeException('No bwtf_content_sync_log table. Nothing to clean up.');
}

$ids = $database->select('bwtf_content_sync_log', 'l')
  ->fields('l', ['destination_id'])
  ->condition('l.entity_type', 'menu_link_content')
  ->execute()
  ->fetchCol();

$ids = array_values(array_filter(array_map('strval', $ids)));
if (!$ids) {
  print 'The sync log records no imported menu links. Nothing to do.' . PHP_EOL;
  return;
}

$storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
$links = $storage->loadMultiple($ids);

$targets = [];
$skipped_menu = 0;
$skipped_protected = 0;
foreach ($links as $link) {
  $menu_name = $link->getMenuName();
  if ($menu_filter !== '' && $menu_name !== $menu_filter) {
    $skipped_menu++;
    continue;
  }
  if ($protected && isset($protected[strtolower((string) $link->uuid())])) {
    $skipped_protected++;
    continue;
  }
  $targets[] = $link;
}

printf('Menu links recorded by the sync: %d%s', count($ids), PHP_EOL);
printf('Still present on this site:      %d%s', count($links), PHP_EOL);
if ($menu_filter !== '') {
  printf('Outside menu "%s", skipped:     %d%s', $menu_filter, $skipped_menu, PHP_EOL);
}
if ($protected) {
  printf('Pre-existing navigation, kept:  %d%s', $skipped_protected, PHP_EOL);
}
else {
  print 'NO protect list given: every link below is only a preview. Build the' . PHP_EOL;
  print 'list from the pre-sync dump before applying, or the site navigation' . PHP_EOL;
  print 'will be removed along with the imported links.' . PHP_EOL;
}
printf('In scope for this run:           %d%s', count($targets), PHP_EOL);

$by_menu = [];
foreach ($targets as $link) {
  $menu_name = $link->getMenuName();
  $by_menu[$menu_name] = isset($by_menu[$menu_name]) ? $by_menu[$menu_name] + 1 : 1;
}
if ($by_menu) {
  print PHP_EOL . 'By menu:' . PHP_EOL;
  foreach ($by_menu as $menu_name => $count) {
    printf('  %-24s %d%s', $menu_name, $count, PHP_EOL);
  }
}

print PHP_EOL . 'Sample (up to 20):' . PHP_EOL;
$shown = 0;
foreach ($targets as $link) {
  if ($shown++ >= 20) {
    break;
  }
  printf(
    '  [%s] menu=%-14s enabled=%d  %s -> %s%s',
    $link->id(),
    $link->getMenuName(),
    (int) $link->isEnabled(),
    mb_substr($link->getTitle(), 0, 44),
    $link->getUrlObject()->toUriString(),
    PHP_EOL
  );
}

if (!$apply) {
  print PHP_EOL . 'Dry run. Nothing was changed.' . PHP_EOL;
  print 'Re-run with --disable --apply to hide them, or --delete --apply to remove them.' . PHP_EOL;
  return;
}

$done = 0;
foreach ($targets as $link) {
  if ($delete) {
    $link->delete();
  }
  else {
    $link->set('enabled', 0);
    $link->save();
  }
  $done++;
}

// Forget them, so a later run does not try to act on them again.
if ($delete && $menu_filter === '' && $targets) {
  $source_ids = [];
  foreach ($targets as $link) {
    $source_ids[] = (string) $link->id();
  }
  $database->delete('bwtf_content_sync_log')
    ->condition('entity_type', 'menu_link_content')
    ->condition('destination_id', $source_ids, 'IN')
    ->execute();
}

printf('%s %d menu link(s).%s', $delete ? 'Deleted' : 'Disabled', $done, PHP_EOL);
print 'Rebuild caches next: drush cr' . PHP_EOL;
