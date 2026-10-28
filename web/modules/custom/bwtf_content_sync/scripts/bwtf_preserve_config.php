<?php

/**
 * @file
 * Keeps the synced homepage slider values across a config import.
 *
 * The content sync writes the production slider into bwtf.settings, but
 * config/sync holds an older copy, so every deploy's drush cim would put that
 * copy back. The deploy saves the slider keys before cim and restores them
 * after it.
 *
 * Only the keys the sync takes from production are handled. Everything else in
 * bwtf.settings still comes from the repository.
 *
 * Save:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_preserve_config.php -- --save=/path/slider.json
 *
 * Restore:
 * vendor/bin/drush scr web/modules/custom/bwtf_content_sync/scripts/bwtf_preserve_config.php -- --restore=/path/slider.json
 */

use Drupal\bwtf_content_sync\PackageUtils;

$options = PackageUtils::parseArguments(isset($extra) && is_array($extra) ? $extra : []);

$is_synced_key = function ($key) {
  return $key === 'slider_display' || preg_match('/^slide\d+_(title|desc|url|image)$/', (string) $key);
};

$config = \Drupal::configFactory()->getEditable('bwtf.settings');

if (!empty($options['save'])) {
  $values = [];
  foreach ($config->getRawData() as $key => $value) {
    if ($is_synced_key($key)) {
      $values[$key] = $value;
    }
  }
  PackageUtils::writeJson($options['save'], $values);
  printf('Saved %d slider setting(s) to %s%s', count($values), $options['save'], PHP_EOL);
  return;
}

if (!empty($options['restore'])) {
  $values = PackageUtils::readJson($options['restore']);
  if (!is_array($values) || !$values) {
    print 'Nothing to restore.' . PHP_EOL;
    return;
  }
  $restored = 0;
  foreach ($values as $key => $value) {
    if ($is_synced_key($key)) {
      $config->set($key, $value);
      $restored++;
    }
  }
  $config->save();
  printf('Restored %d slider setting(s).%s', $restored, PHP_EOL);
  return;
}

throw new RuntimeException('Pass --save=/path/file.json or --restore=/path/file.json.');
