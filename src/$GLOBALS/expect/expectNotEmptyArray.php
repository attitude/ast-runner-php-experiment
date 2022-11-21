<?php

function expectNotEmptyArray($value, string $message = null): array {
  expectArray($value);

  if (count($value) > 0) {
    return $value;
  }

  throwTypeError(
    $message,
    "Expected array to be not empty",
  );
}
