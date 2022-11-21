<?php

namespace Javascript;

class RegExp {
  public function __construct(public readonly string $pattern) {
  }

  public function __toString(): string {
    return $this->pattern;
  }
}
