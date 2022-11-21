<?php

if (!function_exists('array_pick')) {
  function array_pick(array $value, string|int|array|\Closure $pick) {
    $new = [];

    if ($pick instanceof \Closure) {
      foreach ($value as $k => $v) {
        if (expectBoolean($pick($v))) {
          $new[$k] = $v;
        }
      }
    } else {
      if (is_string($pick) || is_int($pick)) {
        $pick = [$pick];
      }

      foreach ($value as $k => $v) {
        if (in_array($k, $pick)) {
          $new[$k] = $v;
        }
      }
    }

    return $new;
  }
}
