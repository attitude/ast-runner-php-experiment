<?php

function expectArray($value, string $message = null) {
  if (is_array($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected array but got %s",
        gettype($value)
      ),
    );
  }
}
