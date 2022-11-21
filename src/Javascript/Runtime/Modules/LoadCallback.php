<?php

namespace Javascript;

final class LoadCallback {
  public function __construct(private \Closure $loader) {
  }

  public function __invoke(string $module) {
    return ($this->loader)($module);
  }
}
