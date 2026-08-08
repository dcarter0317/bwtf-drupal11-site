<?php

namespace Drupal\bwtf_content_sync;

/**
 * Shared package utilities.
 */
class PackageUtils {

  /**
   * Creates a directory recursively.
   *
   * @param string $directory
   *   Directory path.
   *
   * @throws \RuntimeException
   */
  public static function ensureDirectory($directory) {
    if (is_dir($directory)) {
      return;
    }

    if (!mkdir($directory, 0775, TRUE) && !is_dir($directory)) {
      throw new \RuntimeException(sprintf('Unable to create directory: %s', $directory));
    }
  }

  /**
   * Writes JSON to disk.
   *
   * @param string $path
   *   Destination path.
   * @param mixed $data
   *   JSON-serializable data.
   */
  public static function writeJson($path, $data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === FALSE) {
      throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
    }

    if (file_put_contents($path, $json . PHP_EOL) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to write JSON file: %s', $path));
    }
  }

  /**
   * Reads JSON from disk.
   *
   * @param string $path
   *   File path.
   *
   * @return mixed
   *   Decoded JSON.
   */
  public static function readJson($path) {
    if (!is_file($path)) {
      throw new \RuntimeException(sprintf('JSON file not found: %s', $path));
    }

    $json = file_get_contents($path);
    if ($json === FALSE) {
      throw new \RuntimeException(sprintf('Unable to read JSON file: %s', $path));
    }

    $data = json_decode($json, TRUE);
    if ($data === NULL && json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException(sprintf('Invalid JSON in %s: %s', $path, json_last_error_msg()));
    }

    return $data;
  }

  /**
   * Creates a stable SHA-256 hash for nested data.
   *
   * @param mixed $data
   *   Data to hash.
   *
   * @return string
   *   SHA-256 hash.
   */
  public static function hashData($data) {
    self::sortRecursive($data);
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === FALSE) {
      throw new \RuntimeException('Unable to encode data for hashing: ' . json_last_error_msg());
    }
    return hash('sha256', $json);
  }

  /**
   * Recursively sorts associative arrays while retaining list order.
   *
   * @param mixed $data
   *   Data passed by reference.
   */
  public static function sortRecursive(&$data) {
    if (!is_array($data)) {
      return;
    }

    foreach ($data as &$value) {
      self::sortRecursive($value);
    }
    unset($value);

    if (!self::isList($data)) {
      ksort($data);
    }
  }

  /**
   * Determines whether an array is a sequential list.
   *
   * @param array $array
   *   Array to inspect.
   *
   * @return bool
   *   TRUE when the array is a list.
   */
  public static function isList(array $array) {
    $expected = 0;
    foreach ($array as $key => $unused) {
      if ($key !== $expected) {
        return FALSE;
      }
      $expected++;
    }
    return TRUE;
  }

  /**
   * Parses Drush php:script extra arguments.
   *
   * Supports --key=value, --key value, and positional arguments.
   *
   * @param array $extra
   *   The Drush-provided $extra array.
   *
   * @return array
   *   Parsed options and positional values.
   */
  public static function parseArguments(array $extra) {
    $parsed = ['_positional' => []];
    $count = count($extra);

    for ($i = 0; $i < $count; $i++) {
      $argument = $extra[$i];
      if (substr($argument, 0, 2) !== '--') {
        $parsed['_positional'][] = $argument;
        continue;
      }

      $argument = substr($argument, 2);
      $equals = strpos($argument, '=');
      if ($equals !== FALSE) {
        $key = substr($argument, 0, $equals);
        $parsed[$key] = substr($argument, $equals + 1);
        continue;
      }

      $key = $argument;
      $next = $i + 1 < $count ? $extra[$i + 1] : NULL;
      if ($next !== NULL && substr($next, 0, 2) !== '--') {
        $parsed[$key] = $next;
        $i++;
      }
      else {
        $parsed[$key] = TRUE;
      }
    }

    return $parsed;
  }

  /**
   * Converts a stream-wrapper URI to a package-relative binary path.
   *
   * @param string $uri
   *   File URI such as public://2026-07/image.jpg.
   *
   * @return string
   *   Package-relative path.
   */
  public static function uriToPackagePath($uri) {
    $parts = explode('://', $uri, 2);
    if (count($parts) === 2) {
      $scheme = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $parts[0]);
      $target = ltrim($parts[1], '/');
      return 'files/' . $scheme . '/' . $target;
    }

    return 'files/unknown/' . ltrim($uri, '/');
  }

}
