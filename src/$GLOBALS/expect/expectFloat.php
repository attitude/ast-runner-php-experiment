<?php

function expectFloat($value, string $message = null): float {
  if (is_float($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected float but got %s",
        gettype($value)
      ),
    );
  }
}
