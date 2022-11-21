<?php

namespace Javascript;

return [
  'constructor' => function (Instance $value = null) {
    throw new \Exception('First finish object inheritance...');
    return new Instance($value === null ? null : Instance::valueOf($value), $this);
  },
  '[[Prototype]]' => 'Object',
];
