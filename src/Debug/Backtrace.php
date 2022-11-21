<?php

namespace Backtrace;

class Debug {
  public static function dump($value, int $offset = 0, bool $open = false) {
    $backtrace = array_slice(debug_backtrace(0, 2 + $offset), $offset);

    echo '<div style="margin: 0.5em 0; border: 1px solid blue; border-radius: 0.5em;">';

    static::printDetails(
      static::linkPath($backtrace[0]['file'], $backtrace[0]['line']) . (
        ($backtrace[1] ?? null)
        ? ($backtrace[1]['function'] ? ' — ' . $backtrace[1]['function'] . '()' : '')
        : ''
      ),
      $value,
      $open,
    );

    echo '</div>';
  }

  private static function ob_dump($value, int $depth = 0, bool $open = false) {
    if (is_array($value)) {
      $out = [];

      foreach ($value as $k => $v) {
        $out[] = str_repeat("  ", $depth + 1)
          . json_encode($k)
          . ": "
          . trim(static::ob_dump($v, $depth + 1));
      }

      $out[] = str_repeat('  ', $depth) . ']';

      $out = str_repeat('  ', $depth) . "[\n" . implode(",\n", $out);

      return $depth === 0 ? $out : static::getDetails(
        '',
        $out,
      );
    } else {
      ob_start();
      var_dump($value);
      $dump = ob_get_clean();

      if (is_object($value)) {
        $headEndIndex = strpos($dump, '{');

        return static::getDetails(
          trim(substr($dump, 0, $headEndIndex)),
          substr($dump, $headEndIndex),
          $open,
        );
      } else {
        return $dump;
      }
    }
  }

  public static function printDetails(string $summary, $value, bool $open = false) {
    echo static::getDetails($summary, static::ob_dump($value, 0, $open), $open);
  }

  public static function getDetails(string $summary, string $value, bool $open = false) {
    return '<details'
      . ($open ? ' open' : '')
      . ' style="border: 1px solid gray; border-radius: 0.5em; padding: 0.25em 0.5em 0.25em 0.75em;">'
      . '<summary>' . $summary . '</summary><pre><code>'
      . preg_replace("/=>\n\s+/", '=> ', $value)
      . '</code></pre></details>';
  }

  public static function linkPath(string $file, int $line = null): string {
    static $cwd = null;

    if ($cwd === null) {
      $cwd = getcwd();
    }

    return '<a href="vscode-insiders://file'
      . $file
      . ($line === null ? '' : ":{$line}")
      . '">'
      . str_replace($cwd, '', $file)
      . ($line === null ? '' : ":{$line}")
      . '</a>';
  }

  private static function __linkFiles(array $step): array {
    if (array_key_exists('file', $step)) {
      $step['file'] = static::linkPath($step['file'], $step['line']);
      unset($step['line']);
    }

    return $step;
  }

  private static function __compactFunction(array $step): array {
    if (array_key_exists('class', $step)) {
      if (array_key_exists('type', $step)) {
        $step['function'] = $step['class'] . $step['type'] . $step['function'];
        unset($step['type'], $step['class']);
      }
    }

    return $step;
  }

  public static function printBacktraceStep(array $step) {
    $step = static::__compactFunction(
      static::__linkFiles($step)
    );

    $function = $step['function'] ?? null;
    $file = $step['file'] ?? null;
    $args = $step['args'] ?? null;

    unset(
      $step['function'],
      $step['file'],
      $step['args'],
    );

    static::printDetails(
      ($function ? "{$function} " : '') . $file,
      $args,
    );
  }

  public static function printBacktrace(array $backtrace = null, int $flags = 0, array $exclude = ['class' => 'esphp\Scope', 'function' => 'esphp\{closure}']) {
    if ($backtrace === null) {
      $backtrace = array_slice(debug_backtrace($flags), 1);
    }

    echo '<div style="display: flex; flex-direction: column; gap: 0.25em; border: 1px solid lightgray; border-radius: 0.75em; padding: 0.25em; margin: 1em 0;">';

    foreach ($backtrace as $step) {
      $print = true;

      foreach ($exclude as $key => $pattern) {
        $value = $step[$key] ?? null;

        if ($value) {
          foreach ((array) $pattern as $match) {
            if ($value === $match) {
              $print = false;
              break;
            }
          }
        }
      }

      if ($print) {
        static::printBacktraceStep($step);
      }
    }

    echo '</div>';
  }
}
