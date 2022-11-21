<?php

if (!function_exists('items')) {
  function items($value) {
    if (is_array($value)) {
      return is_associative_array($value) ? [$value] : $value;
    } else {
      return [$value];
    }
  }
}
