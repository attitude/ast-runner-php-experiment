<?php

namespace Javascript;

class ArrayExpression {
  public function __construct(private array $value) {
    expectNumericallyIndexedArray($value);

    foreach ($this->value as &$_value) {
      $_value = Literal::from($_value);
    }
  }

  public static function valueOf(ArrayExpression $instance) {
    return $instance->value;
  }
}