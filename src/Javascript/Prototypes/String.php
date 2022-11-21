<?php

namespace Javascript;

return [
  'constructor' => function (Instance $value) {
    return new Instance(Instance::valueOf($value), $this);
  },
  'trim' => function () {
    return Literal::String(trim(Instance::valueOf($this)));
  },
];
