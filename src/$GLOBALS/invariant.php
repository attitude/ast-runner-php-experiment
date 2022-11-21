<?php

function invariant($tested, callable $test, string $message) {
  if (call_user_func($test, $tested) === false) {
    throw new \Exception($message);
  }
}

function expectEmptyArray(array $tested) {
  invariant($tested, fn ($value) => count($value) === 0, 'Unexpected non-empty array');
}
