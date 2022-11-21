<?php

function rest(array $declarations, array $value) {
  $keys = array_keys($value);
  $numbers = 0;
  $strings = 0;

  foreach ($keys as $key) {
    if (is_int($key)) {
      $numbers++;
    } else {
      $strings++;
    }
  }

  if ($numbers > 0 && $strings > 0) {
    throw new \Exception('Cannot mix numeric and string keys when destructuring arrays.');
  }

  $mode = $numbers > 0 ? 'keep' : 'alias';

  $new = [];

  $restValue = [];
  $restKey = null;
  $restKeys = array_keys($value);

  foreach ($declarations as $key => $alias) {
    if ($mode === 'alias' && is_int($key)) {
      $key = $alias;
    }

    if ($key === '...') {
      $restKey = $alias;
    } else if ($restKey !== null) {
      throw new \Exception("Rest key must be the last in destructuring array");
    }

    $new[$alias] = $value[$key] ?? null;
    $restKeys = array_diff($restKeys, [$key]);
  }

  if ($restKey !== null) {
    foreach ($restKeys as $key) {
      if (array_key_exists($key, $value)) {
        $restValue[$key] = $value[$key];
      }
    }

    $new[$restKey] = $restValue;
  }

  return $new;
}
