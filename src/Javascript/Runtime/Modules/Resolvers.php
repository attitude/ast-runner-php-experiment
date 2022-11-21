<?php

namespace Javascript;

use SplObjectStorage;

final class ResolveFailedException extends \Exception {
  public function __construct(
    string $module,
    \Throwable $previous = null,
  ) {
    parent::__construct(
      "Failed to find resolver for '{$module}'",
      404,
      $previous,
    );
  }
}

final class Resolvers {
  private array $keyMap = [];
  private ?SplObjectStorage $regExpMap = null;
  private array $resolved = [];

  public function __construct(...$arguments) {
    $this->regExpMap = new SplObjectStorage();

    $this->register(...$arguments);
  }

  private function setCacheItem(string $id, string $resolved) {
    return $this->resolved[$id] = $resolved;
  }

  private function getCacheItem(string $id): string {
    if (array_key_exists($id, $this->resolved)) {
      return $this->resolved[$id];
    } else {
      throw new UndefinedException("Not yet resolved '{$id}");
    }
  }

  private function clearCache(): void {
    // TODO: Is this the fastest way of skipping the unnecessary re-assignment?
    if (count($this->resolved) > 0) {
      $this->resolved = [];
    }
  }

  public function register(...$arguments) {
    $this->clearCache();

    $argumentsCount = count($arguments);

    if ($argumentsCount === 1) {
      $config = $arguments[0];
      expectArray($config, 'Argument #1 should be an array when passed as single argument.');

      foreach ($config as $id => $resolver) {
        if (is_int($id)) {
          if (is_array($resolver) && count($resolver) === 2) {
            $this->add(...$resolver);
          } else {
            throwUnexpected('$resolver', $resolver);
          }
        } else {
          $this->add($id, $resolver);
        }
      }
    } else if ($argumentsCount === 2) {
      $this->add(...$arguments);
    }
  }

  private function add(
    string|RegExp $id,
    string|ResolveCallback $resolver,
  ) {
    $this->clearCache();

    if (is_string($id)) {
      if (DEV && array_key_exists($id, $this->keyMap)) {
        error_log("Module alias for '{$id} already exists in the key map");
      }

      $this->keyMap[$id] = $resolver;
    } else if ($id instanceof RegExp) {
      if (DEV && isset($this->regExpMap[$id])) {
        error_log("Module add for /{$id}/ already exists");
      }

      $this->regExpMap[$id] = $resolver;
    } else {
      throwUnexpected('$id', $id);
    }
  }

  public function resolve(string $module) {
    if (isset($this->keyMap[$module])) {
      return $this->keyMap[$module] instanceof ResolveCallback
        ? $this->keyMap[$module]($module)
        : $this->keyMap[$module];
    } else {
      $resolved = maybe(fn () => $this->getCacheItem($module), isUndefinedException);

      if ($resolved) {
        return $resolved;
      }

      foreach ($this->regExpMap as $regExp) {
        $resolver = $this->regExpMap[$regExp];

        if (preg_match((string) $regExp, $module)) {
          if (is_string($resolver)) {
            $maybeNewId = preg_replace((string) $regExp, $resolver, $module);

            if ($maybeNewId !== $module) {
              return $this->setCacheItem($module, $maybeNewId);
            } else {
              return $this->setCacheItem($module, $resolver);
            }
          } else if ($resolver instanceof ResolveCallback) {
            return $this->setCacheItem($module, $resolver($module));
          } else {
            throwUnexpected('$resolver', $resolver);
          }
        }
      }
    }

    throw new ResolveFailedException($module);
  }
}
