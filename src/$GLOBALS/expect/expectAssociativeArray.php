<?php

function expectAssociativeArray($value, string $message = null): array {
  expectArray($value);

  try {
    if (!is_associative_array($value)) {
      throwTypeError(
        $message,
        "Expected array to be associative",
      );
    }
  } catch (\EmptyArrayException $th) {
  }

  return $value;
}
