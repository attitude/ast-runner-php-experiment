<?php

namespace Javascript;

use SplObjectStorage;

final class LoadFailedException extends \Exception {
  public function __construct(
    string $module,
    \Throwable $previous = null,
  ) {
    parent::__construct(
      "Failed to find loader for the module '{$module}'",
      404,
      $previous,
    );
  }
}

final class Loaders {
  private array $keyMap = [];
  private ?SplObjectStorage $regExpMap = null;

  public function __construct(...$arguments) {
    $this->regExpMap = new SplObjectStorage();
    $this->register(...$arguments);
  }

  public function register(...$arguments) {
    if (is_array($arguments[0])) {
      $config = $arguments[0];

      foreach ($config as $id => $loader) {
        if (is_int($id)) {
          if (is_array($loader) && count($loader) === 2) {
            $this->add(...$loader);
          } else {
            throwUnexpected('$loader', $loader);
          }
        } else {
          $this->add($id, $loader);
        }
      }
    } else {
      $this->add(...$arguments);
    }
  }

  public function add(string|RegExp $id, LoadCallback $loader) {
    if (is_string($id)) {
      $this->keyMap[$id] = $loader;
    } else {
      $this->regExpMap[$id] = $loader;
    }
  }

  public function load(string $module) {
    if (isset($this->keyMap[$module])) {
      return $this->keyMap[$module]($module);
    } else {
      foreach ($this->regExpMap as $regExp) {
        $loader = $this->regExpMap[$regExp];

        if (preg_match((string) $regExp, $module)) {
          try {
            return $loader($module);
          } catch (LoadFailedException $th) {
            continue;
          }
        }
      }
    }

    throw new LoadFailedException($module);
  }
}
