<?php

if (!defined('VERBOSE_RESOLVE_PATH')) {
  define('VERBOSE_RESOLVE_PATH', false);
}

final class UnresolvedException extends ScopedException {
  public function __construct(
    public readonly string $path,
    array $scope,
    int $code = 0,
    Throwable|null $previous = null,
  ) {
    parent::__construct(
      "Unable to resolve \"{$path}\"",
      $scope,
      $code,
      $previous,
    );
  }
}

define('isUnresolvedException', fn ($thrown) => $thrown instanceof UnresolvedException);

function resolve(string $path, array $extensions = ['php']) {
  if (count($extensions) === 0) {
    throw new Exception("Expecting at least one extension");
  }

  if ($path[0] === '/') {
    $directory = getcwd();

    if (str_starts_with($path, $directory . '/')) {
      $path = substr($path, strlen($directory) + 1);
    } else {
      $path = substr($path, 1);
    }
  } else if ($path[0] === '.') {
    $callstack = debug_backtrace(0);
    $directory = null;

    if (count($callstack) === 1) {
      $callerIndex = 0;
    } else {
      $callerIndex = null;
      $callstack = array_slice($callstack, 1);

      foreach ($callstack as $i => $caller) {
        if (in_array(
          $path,
          $caller['function'] === '__call'
            ? $caller['args'][1]
            : $caller['args']
        )) {
          $callerIndex = $i;
        } else {
          break;
        }
      }
    }

    if ($callerIndex !== null) {
      $directory = dirname($callstack[$callerIndex]['file']);
    } else {
      if ($callstack[0]['function'] === 'require') {
        $directory = dirname($callstack[0]['args'][0]);
      } else {
        $directory = dirname($callstack[1]['file']);
      }
    }

    // if (VERBOSE_RESOLVE_PATH) {
    //   error_log("\$callstack: " . var_dump_get($callstack));
    //   error_log("\$callerIndex: {$callerIndex}");
    //   error_log("\$directory: {$directory}");
    //   error_log("import from \"{$directory}/{$path}\"");
    // }
  }

  $realpath = realpath("{$directory}/{$path}");

  if ($realpath) {
    if (is_dir($realpath)) {
      foreach ($extensions as $ext) {
        $realpathIndex = realpath("{$realpath}/index.{$ext}");

        if ($realpathIndex) {
          return $realpathIndex;
        }
      }

      if (VERBOSE_RESOLVE_PATH) {
        dump($callstack);
      }

      throw new UnresolvedException("{$path}/index file", [
        'realpath' => $realpath,
        'callstack' => $callstack,
      ]);
    } else {
      return $realpath;
    }
  } else {
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $maybePaths = $extension
      ? ["{$directory}/{$path}"]
      : array_map(fn ($ext) => "{$directory}/{$path}.{$ext}", $extensions);

    foreach ($maybePaths as $maybePath) {
      $realpath = realpath($maybePath);

      if ($realpath) {
        return $realpath;
      }
    }

    if (isset($callstack) && VERBOSE_RESOLVE_PATH) {
      dump($callstack);
    }

    throw new UnresolvedException($path, [
      'maybePaths' => $maybePaths,
      'callstack' => $callstack ?? null,
    ]);
  }
}
