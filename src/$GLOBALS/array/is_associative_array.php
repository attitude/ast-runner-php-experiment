<?php

final class EmptyArrayException extends Exception {
  public function __construct() {
    parent::__construct('Array is empty');
  }
}

if (!function_exists('is_associative_array')) {
  function is_associative_array(array $value): bool {
    if (count($value) > 0) {
      $stringKeys = array_filter(array_keys($value), 'is_string');

      return count($stringKeys) > 0;
    } else {
      throw new EmptyArrayException;
    }
  }
}
