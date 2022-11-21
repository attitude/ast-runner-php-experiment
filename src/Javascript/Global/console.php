<?php

namespace Javascript;

return [
  'log' => function (...$arguments) {
    foreach ($arguments as $i => $argument) {
      $value = Instance::valueOf($argument);
      error_log(str_replace('\n', "\n", json_encode($value)));
    }
  },
  '__proto__' => new Instance(
    null,
    Scope::Global(),
    [
      '__proto__' => Scope::Global()->Object->prototype,
    ],
  )
];
