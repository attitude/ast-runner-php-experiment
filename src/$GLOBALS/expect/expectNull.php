<?php

function expectNull($value, string $message = null): void {
  if (is_null($value)) {
    return;
  } else {
    throwTypeError(
      $message,
      sprintf(
        "Expected null but got %s",
        gettype($value)
      ),
    );
  }
}
