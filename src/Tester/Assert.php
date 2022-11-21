<?php

namespace Javascript;

use stdClass;

use Tester\Assert as TesterAssert;

class Assert extends TesterAssert {
  /**
   * @inheritDoc
   */
  public static function equal($expected, $actual, ?string $description = null): void {
    $expectedType = gettype($expected);

    if ($expectedType === gettype($actual) && is_array($expected) || $expected instanceof stdClass) {
      $newExpected = [];
      $optionalKeys = [];

      foreach ((array) $expected as $key => $assertion) {
        $newKey = rtrim($key, '?');

        if ($newKey !== $key) {
          $optionalKeys[] = $newKey;
        }

        $newExpected[$newKey] = $assertion;
      }

      if (!empty($optionalKeys)) {
        $expectedKeys = array_keys((array) $newExpected);
        $actualKeys = array_keys((array) $actual);

        $missingKeys = array_diff($expectedKeys, $actualKeys);

        foreach ($missingKeys as $missingKey) {
          if (in_array($missingKey, $optionalKeys)) {
            unset($newExpected[$missingKey]);
          }
        }

        $expected = $expectedType === 'array' ? $newExpected : (object) $newExpected;
      }
    }

    parent::equal($expected, $actual, $description);
  }
}
