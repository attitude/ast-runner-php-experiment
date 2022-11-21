<?php

function var_dump_get(...$arguments) {
  ob_start();
  var_dump(...$arguments);

  return ob_get_clean();
}
