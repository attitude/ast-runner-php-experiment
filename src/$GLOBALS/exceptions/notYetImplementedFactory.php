<?php

function notYetImplementedFactory(string $name = '') {
  return function () use ($name) {
    throwNotYetImplemented($name);
  };
}
