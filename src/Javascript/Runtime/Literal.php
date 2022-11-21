<?php

namespace Javascript;

abstract class Literal {
  protected $value;
  protected ?Instance $prototype;

  private function __construct() {
  }

  public function __debugInfo() {
    return [
      'protected:value' => $this->value,
    ];
  }

  public function __get(string $name) {
    if ($name !== 'value' && isset($this->{$name})) {
      return $this->{$name};
    } else {
      return $this->prototype->{$name};
    }
  }

  public function __toString() {
    return (string) $this->value;
  }

  public static function valueOf(Literal $instance) {
    return $instance->value;
  }

  public static function from($value) {
    if (
      $value instanceof Instance
      ||
      $value instanceof Literal
      ||
      $value instanceof Resolvable
      ||
      $value instanceof ArrayExpression
      ||
      $value instanceof ObjectExpression
    ) {
      return $value;
    }

    $type = gettype($value);

    switch ($type) {
      case 'boolean':
        return Literal::Boolean($value);
        break;
      case 'integer':
      case 'double':
        return Literal::Number($value);
        break;
      case 'string':
        return Literal::String($value);
        break;
      case 'array':
        if (is_associative_array($value)) {
          return new ObjectExpression($value);
        } else {
          return new ArrayExpression($value);
        }
        break;
      case 'NULL':
        // throw new \Exception('Null is ambiguous in PHP. Call Null() or Undefined() instead');
        return Literal::Undefined();
        break;
      case 'object':
        if ($value instanceof \stdClass) {
          return new ObjectExpression($value);
        } else {
          throwNotYetImplemented('object', $value);
        }
      case 'resource':
      case 'resource (closed)':
      case 'unknown type':
        throwNotYetImplemented("Literal::from({$type})");
        break;
    }
  }

  public static function prototypeOf(Literal $instance) {
    return $instance->prototype;
  }

  public static function Null() {
    static $null = new NullLiteral();

    return $null;
  }

  public static function Undefined() {
    static $undefined = new UndefinedLiteral();

    return $undefined;
  }

  public static function Boolean(bool $value) {
    static $true = new BooleanLiteral(true);
    static $false = new BooleanLiteral(false);;

    return $value ? $true : $false;
  }

  public static function String(string $value) {
    return new StringLiteral($value);
  }

  public static function Number(int|float $value) {
    return new NumberLiteral($value);
  }

  public static function NaN() {
    static $value = null;
    if ($value === null) {
      $value = new NumberLiteral(null);
    }

    return $value;
  }

  public static function PositiveInfinity() {
    static $value = null;
    if ($value === null) {
      $value = new NumberLiteral(PHP_INT_MAX);
    }

    return $value;
  }

  public static function NegativeInfinity() {
    static $value = null;
    if ($value === null) {
      $value = new NumberLiteral(-1 * PHP_INT_MAX);
    }

    return $value;
  }
}

class UndefinedLiteral extends Literal {
  protected function __construct() {
    $this->value = null;
  }

  public function __toString(): string {
    return 'undefined';
  }
}

class NullLiteral extends Literal {
  protected function __construct() {
    $this->value = null;
  }

  public function __toString(): string {
    return 'null';
  }
}

class BooleanLiteral extends Literal {
  protected function __construct(bool $value) {
    $this->value = $value;
    $this->prototype = Scope::Global()->Boolean->prototype;
  }
}

function expectStringLiteral(StringLiteral $value) {
  return $value;
}

class StringLiteral extends Literal {
  protected function __construct(string $value) {
    $this->value = $value;
    $this->prototype = Scope::Global()->String->prototype;
  }
}

class NumberLiteral extends Literal {
  // TODO: $significand: '-' | '+'
  protected function __construct(int|float $value = null) {
    $this->value = $value;
    $this->prototype = Scope::Global()->Number->prototype;
  }
}
