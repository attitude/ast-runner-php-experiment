<?php

namespace Javascript;

class ExportSpecifier {
  public function __construct(public readonly Identifier $local, public readonly Identifier $exported) {
  }
}
