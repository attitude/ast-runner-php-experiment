<?php

require_once __DIR__ . '/resolve.php';

function import(string $path) {
  static $imported = [];

  if (!array_key_exists($path, $imported)) {
    $realpath = realpath($path);

    if ($realpath && is_file($realpath)) {
      $imported[$path] = require $path;
    } else {
      $resolved = resolve($path);
      $imported[$path] = import($resolved);
    }
  }

  return $imported[$path];
}
