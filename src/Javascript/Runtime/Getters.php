<?php

namespace Javascript;

final class Getters {
  public function __construct(private array $getters) {
  }

  public function __get(string $name) {
    if (array_key_exists($name, $this->getters)) {
      return $this->getters[$name];
    } else {
      throw new \Exception("No getter defined for '{$name}'");
    }
  }

  public function __isset(string $name) {
    return array_key_exists($name, $this->getters);
  }
}
