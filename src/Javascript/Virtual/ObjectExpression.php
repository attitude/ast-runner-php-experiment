<?php

namespace Javascript;

class ObjectExpression {
  private array $value;

  public function __construct(array|object $value) {
    if (is_array($value)) {
      expectAssociativeArray($value);
      $this->value = $value;
    } else {
      $this->value = (array) $value;
    }

    foreach ($this->value as &$_value) {
      $_value = Literal::from($_value);
    }
  }

  public static function valueOf(ObjectExpression $instance): array {
    return (array) $instance->value;
  }

  public function __get(string $name) {
    if (array_key_exists($name, $this->value)) {
      return $this->value[$name];
    } else {
      return null;
    }
  }
}
