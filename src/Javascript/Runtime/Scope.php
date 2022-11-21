<?php

namespace Javascript;

use Exception;

define('isScope', fn ($value): bool => $value instanceof Scope);

class Scope {
  private $__values = [];
  private $__declares = [];
  private $__exports = [];
  private ?Scope $__parent = null;
  private ?Module $__module = null;

  private static Scope $Global;

  public function __construct(...$arguments) {
    $this->__parent = maybe(fn () => array_find($arguments, isScope), isUndefinedException);
    $this->__module = maybe(fn () => array_find($arguments, isModule), isUndefinedException);
  }

  public static function Global(Scope $scope = null) {
    if ($scope === null) {
      return static::$Global;
    } else {
      return static::$Global = $scope;
    }
  }

  public static function moduleOf(Scope $scope): ?Module {
    if ($scope->__module !== null) {
      return $scope->__module;
    } else if ($scope->__parent !== null) {
      return static::moduleOf($scope->__parent);
    } else {
      return null;
    }
  }

  const CONST = 'const';
  const LET = 'let';
  const VAR = 'var';

  public function declare(string $type, string $id, $init = uninitialized): Identifier {
    if (in_array($type, ['const', 'let', 'var'])) {
      if (
        ($type === 'const' || $type === 'let')
        &&
        ($this->declares($id))
      ) {
        throw new \Exception("Cannot redeclare block-scoped {$type} '{$id}'");
      }

      if ($type === 'const') {
        if ($init === uninitialized) {
          throw new \Exception("'const' declarations must be initialized.");
        }
      }

      if (is_string($init)) {
        $init = $this->__get($init);
      }

      $this->__declares[$id] = $type;
      $this->__set($id, $init);

      return new Identifier($id);
    } else {
      throw new \Exception("Unexpected type of variable '{$type}'");
    }
  }

  private function declares(string $id): bool {
    return array_key_exists($id, $this->__declares);
  }

  private function exports(string $id): bool {
    return array_key_exists($id, $this->__exports);
  }

  public static function exportsOf(Scope $scope): array {
    $exports = [];

    foreach ($scope->__exports as $key => $v) {
      $exports[$key] = $scope->__export($key);
    }

    return $exports;
  }

  private function defines(string $id): bool {
    return array_key_exists($id, $this->__values);
  }

  private function __export(string $id) {
    $export = $this->__exports[$id];

    if ($export instanceof Identifier) {
      return $this->{$export};
    } else {
      return $export;
    }
  }

  public function export(string|Identifier|array $id, Identifier|Instance|Literal $value = null) {
    if ($value === null) {
      if (is_array($id)) {
        foreach ($id as $key => $value) {
          if (is_int($key)) {
            if ($value instanceof Identifier) {
              $this->export($value, $value);
            } else {
              throwUnexpected("\$value at index {$key}", $value);
            }
          } else {
            $this->export($key, $value);
          }
        }
      } else if ($id instanceof Identifier || is_string($id)) {
        if ($this->exports((string) $id)) {
          return $this->__export((string)$id);
        } else {
          throwUnexpected("missing export '{$id}'", $this);
        }
      } else {
        throwUnexpected('$id', $id);
      }
    } else if (is_string($id) || $id instanceof Identifier) {
      $this->__exports[(string) $id] = $value;
    } else {
      throwUnexpected('$id', ['$id' => $id, '$value' => '$value']);
    }
  }

  public function __isset(string $id) {
    return $this->exports($id);
  }

  public function __set(string $id, object $init = null) {
    if ($this->declares($id)) {
      $this->__values[$id] = $init;
    } else {
      throw new \Exception("Cannot set undeclared variable '{$id}'");
    }
  }

  public function __get(string $id) {
    if (in_array($id, ['const', 'let', 'var'])) {
      $type = $id;

      return new Proxy($this, [
        '__set' => function (Scope $self, string $id, $init) use ($type) {
          $self->declare($type, $id, $init);
        },
      ]);
      // } else if ($id === 'exports') {
      //   return new Proxy($this, [
      //     '__get' => function (Scope $scope, string $id) {
      //       if ($id === '*') {
      //         return Scope::exportsOf($scope);
      //       } else {
      //         return $scope->export($id);
      //       }
      //     },
      //   ]);
    } else if ($this->defines($id)) {
      if ($this->__values[$id] instanceof Resolvable) {
        $this->__values[$id] = ($this->__values[$id])();
      }

      return $this->__values[$id];
    } else if ($this->__parent !== null) {
      return $this->__parent->$id;
    } else if (static::$Global !== null && $this !== static::$Global) {
      return static::$Global->{$id};
    } else {
      throw new UndefinedException("Identifier '{$id}' does not exist in the current scope");
    }
  }
}
