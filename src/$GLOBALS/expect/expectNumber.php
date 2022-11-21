<?php

function expectNumber($value, string $message = null): int|float {
  if (is_int($value) || is_float($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected number but got %s",
        gettype($value)
      ),
    );
  }
}
