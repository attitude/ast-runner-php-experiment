<?php

namespace Javascript;

use Exception;

define('isUndefinedException', fn ($thrown): bool => $thrown instanceof UndefinedException);

final class UndefinedException extends Exception {
}
