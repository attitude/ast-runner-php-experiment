<?php

const DEV = true;
const PROD = false;

const VERBOSE_RESOLVE_PATH = true;
const VERBOSE_NODE_EVAL = false;

const SKIP_ALL_CACHES = false;

const SKIP_AST_CACHE = SKIP_ALL_CACHES;
const SKIP_JSON_DECODED_CACHE = SKIP_ALL_CACHES;

const ASSERT_AST_TREE = DEV;

const PROFILING_DEFAULT_WARNING_MICROSECONDS = 100;
define('PROFILING_STARTED_AT', microtime(true));

function memory_dump_usage() {
  echo '<pre>';

  foreach ($GLOBALS['memory'] as $row) {
    echo implode("\t", $row) . "\n";
  }

  echo '</pre>';
}

function getGeneratedIn() {
  $time = (microtime(true) - PROFILING_STARTED_AT) * 1_000_000;
  return "Generated in {$time} micro seconds.";
}

function memory_set_usage(string $name) {
  static $startedAt = PROFILING_STARTED_AT;
  static $lastTime = null;
  static $lastName = null;

  $now = microtime(true);

  // if ($startedAt === null) {
  //   $startedAt = $now;
  // }

  $time = ($now - $startedAt) * 1_000_000;
  $delta = $lastTime ? ($now - $lastTime) * 1_000_000 : 0;

  if ($delta > PROFILING_DEFAULT_WARNING_MICROSECONDS) {
    echo "<div style=\"font-family:monospace;margin-top:0.75em;\"><div style=\"width:{$delta}px;background-color:red;border-radius:0.25em;padding:0.25em;\">{$lastName} <—> {$name}</div>Delta between ticks is " . round($delta) . ' > ' . PROFILING_DEFAULT_WARNING_MICROSECONDS . " micro seconds.</div>";
  }

  $GLOBALS['memory'][] = [
    $name,
    $time,
    $delta,
    memory_get_usage(),
  ];

  $lastTime = $now;
  $lastName = $name;

  return $now;
}

memory_set_usage('00-init');

const uninitialized = new stdClass;
const undefined = new stdClass;

require('./src/Debug/Backtrace.php');

require './vendor/autoload.php';
require './src/bootstrap.php';

import('./src/Tester');
import('./src/$GLOBALS');
import('./src/Javascript');

use Javascript\LoadCallback;
use Javascript\Loaders;
use Javascript\LoadFailedException;
use Javascript\Module;
use Javascript\Resolvers;
use Javascript\Program;
use Javascript\RegExp;
use Javascript\ResolveCallback;

use function Javascript\astFile;

function dump($value, bool $open = false) {
  Backtrace\Debug::dump($value, 1, $open);
}

memory_set_usage('01-ready');

try {
  $astResolvers = new Resolvers([
    [new RegExp('%' . __DIR__ . '/tests/(.*\.tsx?)$%'), __DIR__ . '/temp/$1.ast.json'],
    [new RegExp('%' . __DIR__ . '/tests/(.*\.jsx?)$%'), new ResolveCallback(function (string $module) {
      return astFile($module);
    })],
  ]);

  $moduleResolvers = new Resolvers([
    [new RegExp('/\.tsx?$/'), new ResolveCallback(fn (string $module) => resolve($module))],
    [new RegExp('/\.jsx?$/'), new ResolveCallback(fn (string $module) => resolve($module))],
    'react/jsx-runtime' => resolve('/vendor_modules/jsx-runtime'),
    [new RegExp('/.*/'), new ResolveCallback(fn (string $module) => resolve($module, ['php', 'mts', 'mjs', 'tsx', 'ts', 'jsx', 'js']))],
  ]);

  $loaders = new Loaders([
    [new RegExp('/\.[jt]sx?$/'), new LoadCallback(function (string $module) use ($moduleResolvers, $astResolvers) {
      try {
        $path = $moduleResolvers->resolve($module);
        $astPath = $astResolvers->resolve($path);

        return new Module(
          $module,
          $path,
          $astPath,
        );
      } catch (UnresolvedException $thrown) {
        throw new LoadFailedException($module, $thrown);
      }
    })],
    [new RegExp('/.*/'), new LoadCallback(function (string $module) use ($astResolvers, $moduleResolvers) {
      $path = $moduleResolvers->resolve($module);
      $astPath = $astResolvers->resolve($path);

      return new Module(
        $module,
        $path,
        $astPath,
      );
    })],
  ]);

  $program = new Program($loaders);

  import('./src/Javascript/Global');

  memory_set_usage('03-program constructed');

  $program->import(
    './tests/simple.js',
  );

  $exports = $program->import(
    './tests/basic.tsx',
  );

  memory_set_usage('imported');

  // echo $exports['rendered'];
  echo $exports->Header([
    'source' => 'https://www.google.com/images/branding/googlelogo/2x/googlelogo_light_color_272x92dp.png',
    'style' => [
      'backgroundColor' => 'black',
    ],
  ]);

  memory_set_usage('echo');

  if (DEV) {
    echo getGeneratedIn();
  }
} catch (Throwable $th) {
  echo '<h1>[' . get_class($th) . '] ' . $th->getMessage() . '</h1>';
  echo '<p>' . Backtrace\Debug::linkPath($th->getFile(), $th->getLine()) . '</p>';
  echo '<style>details details { display: inline-block; }</style>';

  $trace = $th->getTrace();

  if ($th instanceof Tester\AssertException) {
    $assertion = array_shift($trace);

    Backtrace\Debug::printBacktraceStep($assertion);

    echo '<div style="display:flex;gap: 1em;flex-wrap:wrap"><div style="flex:1">';
    Backtrace\Debug::printDetails('Expected:', $assertion['args'][2] ?? null, true);
    echo '</div><div style="flex:1">';
    Backtrace\Debug::printDetails('Actual:', $assertion['args'][1] ?? null, true);
    echo '</div></div>';

    $previous = $th->getPrevious();

    if ($previous) {
      dump($previous);
    }
  } else {
    $previous = $th->getPrevious();

    if ($previous) {
      dump($previous);
    }

    if ($th instanceof ScopedException) {
      dump($th->scope, true);
    }
  }

  Backtrace\Debug::printBacktrace($trace);
  exit;
}
