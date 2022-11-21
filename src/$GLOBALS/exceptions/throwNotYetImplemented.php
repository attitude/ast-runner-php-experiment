<?php

function throwNotYetImplemented(string $name, ...$arguments) {
  throw new \Exception("Not yet implemented" . ($name ? ": {$name}" : ''));
}
