<?php

namespace Javascript;

use Tester\Expect as TesterExpect;

class Expect extends TesterExpect {
  public static function memberOf(array $haystack, bool $strict = true) {
    return (new self)->and(fn (mixed $needle) => in_array($needle, $haystack, $strict));
  }

  public static function literal() {
    return (new self)->and(fn (string|int|float|bool|null $value) => true);
  }

  public static function union(...$constrains): self {
    $me = new self;

    $me->and(function ($value) use ($constrains) {
      $failures = [];
      $thrown = [];

      foreach ($constrains as $constraint) {
        try {
          $constraint($value);
          return true;
        } catch (\Throwable $th) {
          $failures[] = $th->getMessage();
          $thrown[] = $th;
        }
      }

      throw new \Exception('Expecting either ' . implode(', ', $failures) . '.', 0, $thrown[0]);
    });

    return $me;
  }
}
