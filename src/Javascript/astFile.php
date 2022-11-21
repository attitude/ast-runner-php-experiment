<?php

namespace Javascript;

function transformToESTree(\stdClass $ast) {
  unset($ast->leadingComments);
  unset($ast->trailingComments);

  foreach ((array) $ast as $k => &$v) {
    if ($k === 'location') {
      $ast->loc = $v;
      unset($ast->{$k});
    } else if (is_array($v)) {
      foreach ($v as &$_v) {
        if (is_object($_v)) {
          $_v = transformToESTree($_v);
        }
      }
    } else if (is_object($v)) {
      $v = transformToESTree($v);
    }
  }

  return $ast;
}

function astFile(string $module): string {
  $realPath = realpath($module);

  if ($realPath) {
    $code = file_get_contents($module);

    $sha = hash('sha256', $code);
    $astFile = getcwd() . '/.cache/ast/' . $sha . '.ast.json';

    if (SKIP_AST_CACHE || !file_exists($astFile)) {
      $dirname = dirname($astFile);

      if (!file_exists($dirname)) {
        mkdir($dirname, 0777, true);
      }

      $ast = transformToESTree(json_decode(json_encode(
        \Peast\Peast::latest($code, [
          'sourceType' => 'module',
          'jsx' => true,
        ])->parse()
      )));

      file_put_contents(
        $astFile,
        json_encode($ast, JSON_PRETTY_PRINT),
      );
    }

    return $astFile;
  } else {
    throw new \UnresolvedException($module, [
      '$realPath' => $realPath,
    ]);
  }
}
