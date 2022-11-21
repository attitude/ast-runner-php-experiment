<?php

if (!function_exists('array_flatten')) {
  function array_flatten(array $array): array {
    $return = array();

    array_walk_recursive($array, function ($item, $key) use (&$return) {
      if (is_int($key)) {
        $return[] = $item;
      } else {
        $return[$key] = $item;
      }
    });

    return $return;
  }
}
