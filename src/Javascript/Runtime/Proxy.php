<?php

namespace Javascript;

use ArrayObject;

class Proxy extends ArrayObject {
  public function __construct(private object $target, private array $handler) {
    $extraKeys = array_filter(array_keys($handler), fn ($key) => !in_array($key, ['__get', '__set', '__call']));

    if (count($extraKeys) !== 0) {
      throw new \Exception("Not yet implemented handlers for extra keys");
    }
  }

  public function offsetGet(mixed $key): mixed {
    expectString($key, 'Key must be a string.');

    return $this->__get($key);
  }

  public function __call(string $name, array $args) {
    if (array_key_exists('__call', $this->handler)) {
      return $this->handler['__call']($this->target, $name, $args);
    } else {
      return $this->target->__call($name, $args);
    }
  }

  public function __get(string $name) {
    if (array_key_exists('__get', $this->handler)) {
      return $this->handler['__get']($this->target, $name);
    } else {
      return $this->target->__get($name);
    }
  }

  public function __set(string $name, $value) {
    if (array_key_exists('__set', $this->handler)) {
      return $this->handler['__set']($this->target, $name, $value);
    } else {
      return $this->target->__set($name, $value);
    }
  }
}
