<?php

namespace Javascript;

return [
  'constructor' => function (Instance $value = null) {
    return new Instance($value === null ? null : Instance::valueOf($value), $this);
  },
];
