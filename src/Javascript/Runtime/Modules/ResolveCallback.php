<?php

namespace Javascript;

final class ResolveCallback {
  public function __construct(private \Closure $resolver) {
  }

  public function __invoke(string $id): string {
    return ($this->resolver)($id);
  }
}
