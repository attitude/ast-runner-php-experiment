<?php

function getThrowMessage(...$messages) {
  return trim(implode(' ', array_filter($messages)));
}

function throwTypeError(...$messages) {
  throw new \TypeError(getThrowMessage(...$messages));
}
