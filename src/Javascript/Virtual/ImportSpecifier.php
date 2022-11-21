<?php

namespace Javascript;

class ImportSpecifier {
  public readonly ?Identifier $imported;

  public function __construct(public readonly Identifier $local, Identifier $imported = null) {
    $this->imported = $imported ?? $local;
  }
}