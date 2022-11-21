<?php

function throwUnexpected(string $description, ...$arguments) {
  $count = count($arguments);

  if ($count >= 1) {
    throw new TypeError("Unexpected {$description}.");
  } else {
    throw new \Exception("Expecting at least one argument");
  }
}
