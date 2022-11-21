<?php

function expectBoolean($value, string $message = null): bool {
  if (is_bool($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected boolean but got %s",
        gettype($value)
      ),
    );
  }
}
