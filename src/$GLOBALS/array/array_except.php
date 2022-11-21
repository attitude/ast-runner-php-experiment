<?php

if (!function_exists('array_except')) {
  function array_except(array $value, string|int|array $omit) {
    $new = [];

    if (is_string($omit) || is_int($omit)) {
      $omit = [$omit];
    }

    foreach ($value as $k => $v) {
      if (!in_array($k, $omit)) {
        $new[$k] = $v;
      }
    }

    return $new;
  }
}
