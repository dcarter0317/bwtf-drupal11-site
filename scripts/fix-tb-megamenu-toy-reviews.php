<?php

/**
 * @file
 * Rebuilds the Toy Reviews dropdown column in the TB Mega Menu "main__bwtf".
 *
 * Fixes the duplicated "Generation 2" entry on stage by listing each child
 * link exactly once. Only the Toy Reviews column is changed; every other
 * setting in menu_config / block_config is preserved.
 *
 * Usage (preview, saves nothing):
 *   drush php:script scripts/fix-tb-megamenu-toy-reviews.php
 * Usage (apply):
 *   drush php:script scripts/fix-tb-megamenu-toy-reviews.php -- --save
 * Then: drush cr
 */

$save = in_array('--save', $extra ?? [], TRUE) || in_array('--save', $_SERVER['argv'] ?? [], TRUE);

$parent = 'menu_link_content:43d08505-6ab7-4dd5-8818-b1a79e5b9ac0';
// Stage UUIDs, in display order.
$children = [
  'bed2978e-257b-4805-b818-274fb1a1921f' => 'Generation One',
  'ce5e99e0-cc72-4629-8ebf-8aac1dc7af33' => 'Generation 2',
  'bfbf61c4-dfe7-4c55-86dd-8e5bc947ab19' => 'Beast Wars Transformers',
  '8fca1977-348e-417b-9201-06e621a90081' => 'Beast Wars Neo',
  '0ae196ca-06b6-4e38-9c79-916928d1d9b4' => 'Machine Wars',
  'fda1b45c-a1f0-4ee3-a4d5-e8cd722a1902' => 'Robots in Disguise',
];

$menu = \Drupal::entityTypeManager()->getStorage('tb_megamenu')->load('main__bwtf');
if (!$menu) {
  print "tb_megamenu main__bwtf not found.\n";
  return;
}
$config = json_decode($menu->get('menu_config'), TRUE);
if (!isset($config[$parent])) {
  print "Toy Reviews item ($parent) not found in menu_config; aborting.\n";
  return;
}

$default_item = [
  'rows_content' => [],
  'submenu_config' => ['width' => '', 'class' => '', 'group' => '0'],
  'item_config' => ['class' => '', 'xicon' => '', 'caption' => '', 'alignsub' => '', 'group' => '0', 'hidewcol' => '0', 'hidesub' => '0', 'label' => ''],
];

$col = [];
$weight = -50;
foreach (array_keys($children) as $uuid) {
  $id = "menu_link_content:$uuid";
  $col[] = ['plugin_id' => $id, 'type' => 'menu_item', 'tb_item_config' => [], 'weight' => (string) $weight++];
  $config[$id] = $config[$id] ?? $default_item;
}

$old = $config[$parent]['rows_content'][0][0]['col_content'] ?? [];
$col_config = $config[$parent]['rows_content'][0][0]['col_config'] ?? ['width' => '12', 'class' => '', 'hidewcol' => '0', 'showblocktitle' => '1'];
$config[$parent]['rows_content'] = [[['col_content' => $col, 'col_config' => $col_config]]];

print "Before: " . implode(', ', array_column($old, 'plugin_id')) . "\n";
print "After:  " . implode(', ', array_column($col, 'plugin_id')) . "\n";

if ($save) {
  $menu->set('menu_config', json_encode($config))->save();
  drupal_flush_all_caches();
  print "Saved and caches cleared.\n";
}
else {
  print "Preview only. Re-run with -- --save to apply.\n";
}
