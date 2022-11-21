<?php

namespace Javascript;

use Error;
use ReflectionFunction;

// Notes:
// - \SplObjectStorage might come handy

final class Instance {
  private ProtectedInstance $____protectedValue;

  public static function create(Scope $scope, string $type, ...$arguments) {
    if (count($arguments) === 0) {
      throw new \Exception("Expecting at least one argument (value), 0 given");
    }

    $class = $scope->{$type};
    $constructor = Instance::valueOf($class);

    return ($constructor->bindTo($class, $class)(...$arguments));
  }

  public static function Boolean(Scope $scope, ...$arguments) {
    return static::create($scope, 'Boolean', ...$arguments);
  }

  public static function Number(Scope $scope, ...$arguments) {
    return static::create($scope, 'Number', ...$arguments);
  }

  public static function String(Scope $scope, ...$arguments) {
    return static::create($scope, 'String', ...$arguments);
  }

  public static function Array(Scope $scope, ...$arguments) {
    return static::create($scope, 'Array', ...$arguments);
  }

  public static function Object(Scope $scope, ...$arguments) {
    return static::create($scope, 'Object', ...(empty($arguments) ? [null] : $arguments));
  }

  public static function Function(Scope $scope, ...$arguments) {
    return static::create($scope, 'Function', ...$arguments);
  }

  public function __construct(
    $value,
    Scope $scope,
    array $initializer = []
  ) {
    $this->____protectedValue = new ProtectedInstance($value, $scope, $initializer);
  }

  public function __invoke(...$arguments) {
    if (Instance::typeof($this) === 'function') {
      error_log('TODO: Use prototype.call()');

      $closure = Instance::valueOf($this);
      return $closure(...(array_map(fn ($argument) => Literal::from($argument), $arguments)));
    } else {
      throw new Error("Object of type " . Instance::typeof($this) . " is not callable");
    }
  }

  public function __call(string $id, array $arguments) {
    return ($this->__get($id))(...$arguments);
  }

  public function __get(string $name) {
    return $this->____protectedValue->__get($name);
  }

  public function __set(string $id, $value) {
    if ($value instanceof Identifier) {
      throwUnexpected('$value', [$id => $value]);
    }

    return $this->____protectedValue->__set($id, $value);
  }

  public static function typeof(Instance $instance) {
    $prototypeName = (string) Instance::prototypeOf($instance)->constructor->name;

    switch ($prototypeName) {
      case 'Function':
        return 'function';
        break;
      case 'String':
        return 'string';
        break;
      case 'Number':
        return 'number';
        break;
      case 'Boolean':
        return 'boolean';
        break;
      default:
        throwNotYetImplemented("`typeof` for '{$prototypeName}' prototype");
        break;
    }
  }

  public static function valueOf($instance, array $omit = null) {
    if ($omit) {
      foreach ($omit as &$omit_key) {
        if (!is_string($omit_key)) {
          if ($omit_key instanceof Identifier) {
            $omit_key = (string) $omit_key;
          } else {
            throwUnexpected('$omit_key', $omit_key);
          }
        }
      }
    }

    if ($instance instanceof Literal) {
      if ($omit !== null) {
        throwUnexpected('$omit', $omit);
      }

      return Literal::valueOf($instance);
    } else if ($instance instanceof ArrayExpression) {
      if ($omit !== null) {
        throwUnexpected('$omit', $omit);
      }

      return ArrayExpression::valueOf($instance);
    } else if ($instance instanceof ObjectExpression) {
      if ($omit !== null) {
        return array_filter(
          ObjectExpression::valueOf($instance),
          fn (string $key) => !in_array($key, $omit),
          ARRAY_FILTER_USE_KEY,
        );
      } else {
        return ObjectExpression::valueOf($instance);
      }
    } else if ($instance instanceof FunctionExpression) {
      if ($omit !== null) {
        throwUnexpected('$omit', $omit);
      }

      return FunctionExpression::valueOf($instance);
    } else if ($instance instanceof Instance) {
      return ProtectedInstance::valueOf($instance->____protectedValue, $omit);
    } else if ($instance instanceof ProtectedInstance) {
      return ProtectedInstance::valueOf($instance, $omit);
    } else {
      if ($omit !== null) {
        throwUnexpected('$omit', $omit);
      }

      if (is_array($instance) || $instance instanceof \stdClass) {
        foreach ((array) $instance as &$value) {
          $value = Instance::valueOf($value);
        }
      }

      return $instance;
    }
  }

  public static function prototypeOf($instance) {
    if ($instance instanceof Literal) {
      return Literal::prototypeOf($instance);
    } else if ($instance instanceof Instance) {
      return ProtectedInstance::prototypeOf($instance->____protectedValue);
    } else if ($instance instanceof ProtectedInstance) {
      return ProtectedInstance::prototypeOf($instance);
    } else {
      throw new \Exception('Unable to get prototype of instance');
    }
  }

  public static function scopeOf(ProtectedInstance|Instance $instance): Scope {
    if ($instance instanceof ProtectedInstance) {
      return ProtectedInstance::scopeOf($instance);
    } else {
      return ProtectedInstance::scopeOf($instance->____protectedValue);
    }
  }
}

final class ProtectedInstance {
  private $value;
  private array $properties = [];
  private ?Getters $getters = null;
  private ?Setters $setters = null;
  private null|Instance|Resolvable $prototype;
  private Scope $scope;

  public function __debugInfo() {
    return [
      'private:value' => $this->value,
      'private:properties' => $this->properties,
      'private:prototype.name' => $this->prototype->constructor->name ?? null,
    ];
  }

  public function __construct(
    $value,
    Scope $scope,
    array $initializer = [],
  ) {
    $this->value = $value;
    $this->scope = $scope;

    $keys = array_keys($initializer);

    if (in_array("*value", $keys)) {
      throw new \Exception('WTF?!');
    }

    foreach ($initializer as $property => $init) {
      if ($init instanceof Setters) {
        $this->setters = $init;
      } else if ($init instanceof Getters) {
        $this->getters = $init;
      } else if ($property === '__proto__') {
        $this->prototype = $init;
      } else {
        $this->__set($property, $init);
      }
    }

    if (!array_key_exists('__proto__', $initializer)) {
      throwUnexpected('__proto__', $initializer);
    }
  }

  public function __get(string $name) {
    if (array_key_exists($name, $this->properties)) {
      $value = $this->properties[$name];
    } else {
      if ($this->getters && isset($this->getters->{$name})) {
        $value = ($this->getters->{$name}->bindTo($this))();
      } else if ($this->prototype) {
        if ($this->prototype instanceof Resolvable) {
          $this->prototype = ($this->prototype)();
        }

        return $this->prototype->{$name};
      } else {
        throw new UndefinedException("Property '{$name} does not exist on type {$this->constructor->name}.");
      }
    }

    if ($value instanceof Resolvable) {
      $value = $this->properties[$name] = $value();
    }

    if ($value instanceof Instance) {
      return $value;
    } else {
      $type = gettype($value);

      if ($type === 'object') {
        if ($value instanceof \Closure) {
          $reflection = new ReflectionFunction($value);

          return $this->properties[$name] = Instance::Function(
            $this->scope,
            new FunctionExpression(
              $value->bindTo($this, $this),
              $name,
              $reflection->getNumberOfParameters(),
            ),
          );
        } else if ($value instanceof Literal) {
          return $value;
        } else if ($value instanceof ArrayExpression) {
          return $this->properties[$name] = Instance::Array(
            $this->scope,
            $value,
          );
        } else {
          // See bellow...
        }
      } else if ($type === 'array') {
        return $this->properties[$name] = Instance::Array(
          $this->scope,
          new ArrayExpression($value),
        );
      } else if ($type === 'string') {
        return $this->properties[$name] = Literal::String($value);
      } else {
        // See bellow...
      }

      dump(['$name' => $name, '$value' => $value]);
      throw new \Exception("Not implemented late instantiation: Instance::__get('{$name}'): {$type}");
    }
  }

  public function __set(string $name, $value) {
    if ($this->setters && isset($this->setters->{$name})) {
      ($this->setters->{$name}->bindTo($this))($value);
    } else {
      $this->properties[$name] = $value;
    }
  }

  public static function valueOf(ProtectedInstance $instance, array $omit = null) {
    if ($instance->value === null) {
      $constructorName = (string) $instance->prototype->constructor->name;

      switch ($constructorName) {
        case 'Object':
        case 'Array':
          $return = [];

          foreach ($instance->properties as $property => $value) {
            $include = true;

            if ($omit && in_array($property, $omit)) {
              $include = false;
            }

            if ($include) {
              $valueOf = Instance::valueOf($value);

              if ($valueOf instanceof \Closure) {
                $valueOf = new FunctionExpression(
                  $valueOf,
                  Instance::valueOf($value->name),
                  Instance::valueOf($value->length),
                );
              }

              $return[$property] = $valueOf;
            }
          }

          return $constructorName === 'Object' ? (object) $return : (array) $return;
          break;
        default:
          dump($instance, true);
          notYetImplementedFactory("valueOf('{$instance->prototype->constructor->name}')")();
          break;
      }
    }

    return $instance->value;
  }

  public static function prototypeOf(ProtectedInstance $instance): Instance {
    return $instance->prototype;
  }

  public static function scopeOf(ProtectedInstance $instance): Scope {
    return $instance->scope;
  }
}
