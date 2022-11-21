<?php

class ScopedException extends \Exception {
  public function __construct(
    string $message,
    public readonly array $scope,
    int $code = 0,
    Throwable|null $previous = null,
  ) {
    parent::__construct(
      $message,
      $code,
      $previous,
    );
  }
}
