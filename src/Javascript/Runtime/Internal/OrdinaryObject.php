<?php

namespace Javascript\Internal;

final class NullObject {
}

final class OrdinaryObject {
  // public function __call(string $id, ...$arguments) {
  //   match ($id) {
  //     '[[GetPrototypeOf]]' => fn () => OrdinaryGetPrototypeOf($this),
  //     '[[SetPrototypeOf]]' => fn () => OrdinarySetPrototypeOf($this, ...$arguments),
  //   };
  // }

  public function __set(string $id, $value) {
  }
}

// https://tc39.es/ecma262/#sec-makebasicobject
// function MakeBasicObject(array $internalSlotsList): OrdinaryObject {
//   // $obj =
// }

function OrdinaryGetPrototypeOf(OrdinaryObject $O) {
  return $O->{'[[Prototype]]'};
}

// function OrdinarySetPrototypeOf(OrdinaryObject $O, OrdinaryObject $value): bool {
//   $current = $O->{'[[Prototype'};
//   //...
// }
