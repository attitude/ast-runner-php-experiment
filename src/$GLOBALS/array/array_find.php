<?php

use Javascript\UndefinedException;

if (!function_exists('array_find')) {
  function array_find(array $value, \Closure $callback) {
    foreach ($value as $v) {
      if (expectBoolean($callback($v))) {
        return $v;
      }
    }

    throw new UndefinedException();
  }
}
