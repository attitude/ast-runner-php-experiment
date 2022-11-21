<?php

namespace Javascript;

final class Entry extends \ArrayObject {
  public function __construct(
    public readonly string $key,
    public readonly Literal|Instance $value,
  ) {
    parent::__construct([
      $key,
      $value,
    ]);
  }
}