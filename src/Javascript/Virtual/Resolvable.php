<?php

namespace Javascript;

class Resolvable {
  public function __construct(private \Closure $resolver) {
  }

  public function __invoke() {
    return ($this->resolver)();
  }
}
