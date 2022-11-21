<?php

namespace Javascript;

class FunctionExpression {
  public function __construct(private \Closure $value, private string $name, private int $length) {
  }

  public function __get(string $name) {
    if ($name === 'name') {
      return $this->name;
    } else if ($name === 'length') {
      return $this->length;
    } else {
      throw new \Exception("Property '{$name}' does not exist");
    }
  }

  public static function valueOf(FunctionExpression $instance) {
    return $instance->value;
  }
}