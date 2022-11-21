<?php

namespace Javascript;

final class Identifier {
  public function __construct(public readonly string $id) {
  }

  public function __toString(): string {
    return $this->id;
  }
}