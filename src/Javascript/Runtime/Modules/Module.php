<?php

namespace Javascript;

define('isModule', fn ($value): bool => $value instanceof Module);

final class Module {
  public readonly array $code;
  public readonly \stdClass $ast;

  public function __construct(
    public readonly string $module,
    public readonly string $path,
    public readonly string $astPath,
  ) {
    $this->loadCode($path);
    $this->loadAst($astPath);
  }

  private function loadCode(string $path) {
    $this->code = explode("\n", str_replace("\n\r", "\n", $this->read($path)));
  }

  private function loadAst(string $path) {
    if (str_ends_with($path, '.json')) {
      $this->ast = file_get_json_decoded($path);
    } else {
      throwUnexpected('$path', $path);
      // $this->ast = require $path;
    }
  }

  private function read(string $path) {
    $_relativePath = str_replace(getcwd(), '', $path);

    if (realpath($path)) {
      return file_get_contents($path);
    } else {
      throw new \Exception("File does not exist: '{$_relativePath}'");
    }
  }
}
