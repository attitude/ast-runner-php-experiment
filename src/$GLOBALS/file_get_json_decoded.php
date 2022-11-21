<?php

if (!defined('JSON_DECODED_CACHE_DIR')) {
  define('JSON_DECODED_CACHE_DIR', getcwd() . '/.cache/JSON_DECODED');
}

if (!defined('SKIP_JSON_DECODED_CACHE')) {
  define('SKIP_JSON_DECODED_CACHE', false);
}

if (file_exists(JSON_DECODED_CACHE_DIR)) {
  if (!is_dir(JSON_DECODED_CACHE_DIR)) {
    throw new Exception('Cache path is not a directory');
  }
} else {
  mkdir(JSON_DECODED_CACHE_DIR, 0777, true);
}

if (!function_exists('file_get_json_decoded')) {
  function file_get_json_decoded(string $filename,) {
    $stat = stat($filename);

    if (!$stat) {
      return json_decode(file_get_contents($filename));
    }

    $cached = JSON_DECODED_CACHE_DIR . '/' . hash('sha256', $filename);

    if (!SKIP_JSON_DECODED_CACHE && file_exists($cached)) {
      $contents = require($cached);
      $skipCache = false;

      foreach ($contents['stat'] as $key => $value) {
        if (!$skipCache && $stat[$key] !== $value) {
          $skipCache = true;
        }
      }

      if (!$skipCache) {
        return $contents['data'];
      }
    }

    $contents = file_get_contents($filename);

    if ($contents === null) {
      throw new Exception("Failed to read contents of file");
    }

    $data = json_decode($contents);

    if ($data === null) {
      throw new Exception("Failed to decode JSON contents");
    }

    file_put_contents(
      $cached,
      '<?php return ' . var_export([
        'stat' => $stat,
        'data' => $data,
      ], true) . ';',
    );

    return $data;
  }
}
