<?php

function expectNumericallyIndexedArray($value, string $message = null): array {
  expectArray($value);

  try {
    if (is_associative_array($value)) {
      throwTypeError(
        $message,
        "Expected array to be numerically indexed",
      );
    }
  } catch (\EmptyArrayException $th) {
  }

  return $value;
}
