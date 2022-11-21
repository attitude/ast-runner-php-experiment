<?php

function expectInt($value, string $message = null): int {
  if (is_int($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected int but got %s",
        gettype($value)
      ),
    );
  }
}
