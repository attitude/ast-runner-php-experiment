<?php

namespace Javascript;

function throwStrictModeFactory() {
  return function () {
    throw new \TypeError("TypeError: 'caller', 'callee', and 'arguments' properties may not be accessed on strict mode functions or the arguments objects for calls to them");
  };
}
