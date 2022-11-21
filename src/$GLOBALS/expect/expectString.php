<?php

function expectString($value, string $message = null): string {
  if (is_string($value)) {
    return $value;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected string but got %s",
        gettype($value)
      ),
    );
  }
}
