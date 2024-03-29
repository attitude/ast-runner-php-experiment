<?php

namespace Javascript\Internal;

final class Slot {
  static array $map = [];

  private function __construct(public readonly string $id) {
    if (!str_starts_with($id, '[[') || !str_ends_with($id, ']]')) {
      throw new \Exception("Expecting slot described as '[[" . trim($id, '[]') . "]]', '{$id}' given.");
    }
  }

  public static function for(string $id) {
    if (!isset(static::$map[$id])) {
      static::$map[$id] = new Slot($id);
    }

    return static::$map[$id];
  }
}
