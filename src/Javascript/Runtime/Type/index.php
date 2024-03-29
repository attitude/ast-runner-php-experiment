<?php

namespace Javascript;

abstract class Type {
}

final class BooleanType {
  public static function literal(bool $value) {
    static $true = new BooleanType(true);
    static $false = new BooleanType(true);

    return $value ? $true : $false;
  }
}
