<?php

function maybe(\Closure $closure, \Closure $assertThrowable = null) {
  try {
    return $closure();
  } catch (\Throwable $th) {
    if ($assertThrowable && !$assertThrowable($th)) {
      throw $th;
    }

    return null;
  }
}
