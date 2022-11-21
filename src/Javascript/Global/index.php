<?php

namespace Javascript;

use Exception;

Scope::Global(new Scope());

Scope::Global()->const->undefined = new Resolvable(fn () => Literal::Undefined());
Scope::Global()->const->NaN = new Resolvable(fn () => Literal::NaN());
Scope::Global()->const->Infinity = new Resolvable(fn () => Literal::PositiveInfinity());

Scope::Global()->const->Object = new Instance(
  function (Literal|ArrayExpression|ObjectExpression|Instance $value = null) {
    $scope = Instance::scopeOf($this);

    if ($value instanceof BooleanLiteral) {
      return Instance::Boolean($scope, $value);
    } else if ($value instanceof NumberLiteral) {
      return Instance::Number($scope, $value);
    } else if ($value instanceof StringLiteral) {
      return Instance::String($scope, $value);
    } else if ($value instanceof ArrayExpression) {
      return Instance::Array($scope, $value);
    } else if ($value === null || $value instanceof NullLiteral || $value instanceof ObjectExpression) {
      return new Instance(
        null,
        $scope,
        [
          ...(Instance::valueOf($value) ?? []),
          '__proto__' => $this->prototype,
        ],
      );
    } else if ($value instanceof Instance) {
      return $value;
    } else {
      throw new \Exception("Unexpected else branch");
    }
  },
  Scope::Global(),
  [
    'assign' => notYetImplementedFactory('Object.assign'),
    'create' => notYetImplementedFactory('Object.create'),
    'defineProperties' => notYetImplementedFactory('Object.defineProperties'),
    'defineProperty' => notYetImplementedFactory('Object.defineProperty'),
    'entries' => notYetImplementedFactory('Object.entries'),
    'freeze' => notYetImplementedFactory('Object.freeze'),
    'fromEntries' => notYetImplementedFactory('Object.fromEntries'),
    'getOwnPropertyDescriptor' => notYetImplementedFactory('Object.getOwnPropertyDescriptor'),
    'getOwnPropertyDescriptors' => notYetImplementedFactory('Object.getOwnPropertyDescriptors'),
    'getOwnPropertyNames' => notYetImplementedFactory('Object.getOwnPropertyNames'),
    'getOwnPropertySymbols' => notYetImplementedFactory('Object.getOwnPropertySymbols'),
    'getPrototypeOf' => notYetImplementedFactory('Object.getPrototypeOf'),
    'hasOwn' => notYetImplementedFactory('Object.hasOwn'),
    'is' => notYetImplementedFactory('Object.is'),
    'isExtensible' => notYetImplementedFactory('Object.isExtensible'),
    'isFrozen' => notYetImplementedFactory('Object.isFrozen'),
    'isSealed' => notYetImplementedFactory('Object.isSealed'),
    'keys' => notYetImplementedFactory('Object.keys'),
    'length' => 1,
    'name' => 'Object',
    'preventExtension' => notYetImplementedFactory('Object.preventExtension'),
    'prototype' => new Instance(
      null,
      Scope::Global(),
      [
        'constructor' => new Resolvable(fn () => Scope::Global()->Object),
        'hasOwnProperty' => notYetImplementedFactory('$Object.hasOwnProperty'),
        'isPrototypeOf' => notYetImplementedFactory('$Object.isPrototypeOf'),
        'propertyIsEnumerable' => notYetImplementedFactory('$Object.propertyIsEnumerable'),
        'toLocaleString' => notYetImplementedFactory('$Object.toLocaleString'),
        'toString' => notYetImplementedFactory('$Object.toString'),
        'valueOf' => notYetImplementedFactory('$Object.valueOf'),
        '__defineGetter__' => notYetImplementedFactory('$Object.__defineGetter__'),
        '__defineSetter__' => notYetImplementedFactory('$Object.__defineSetter__'),
        '__lookupGetter__' => notYetImplementedFactory('$Object.__defineGetter__'),
        '__lookupSetter__' => notYetImplementedFactory('$Object.__defineGetter__'),
        new Getters([
          '__proto__' => function () {
            throw new Exception("Use of '__proto__' is deprecated. Read more at https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/proto");
          },
        ]),
        '__proto__' => null,
      ]
    ),
    'seal' => notYetImplementedFactory('Object.seal'),
    'setPrototypeOf' => notYetImplementedFactory('Object.setPrototypeOf'),
    'values' => notYetImplementedFactory('Object.values'),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Function->prototype),
  ],
);

Scope::Global()->const->Array = new Instance(
  function (ArrayExpression $value) {
    $scope = Instance::scopeOf($this);

    return new Instance(
      null,
      $scope,
      [
        ...(Instance::valueOf($value) ?? []),
        '__proto__' => $this->prototype,
      ],
    );
  },
  Scope::Global(),
  [
    'from' => notYetImplementedFactory('Array.from()'),
    'isArray' => notYetImplementedFactory('Array.isArray()'),
    'length' => 1,
    'name' => 'Array',
    'of' => notYetImplementedFactory('Array.of()'),
    'prototype' => new Instance(null, Scope::Global(), [
      'at' => notYetImplementedFactory('Array.at()'),
      'concat' => notYetImplementedFactory('Array.concat()'),
      'constructor' => new Resolvable(fn () => Scope::Global()->Array),
      'copyWithin' => notYetImplementedFactory('Array.copyWithin()'),
      'entries' => notYetImplementedFactory('Array.entries()'),
      'every' => notYetImplementedFactory('Array.every()'),
      'fill' => notYetImplementedFactory('Array.fill()'),
      'filter' => notYetImplementedFactory('Array.filter()'),
      'find' => notYetImplementedFactory('Array.find()'),
      'findIndex' => notYetImplementedFactory('Array.findIndex()'),
      'findLast' => notYetImplementedFactory('Array.findLast()'),
      'findLastIndex' => notYetImplementedFactory('Array.findLastIndex()'),
      'flat' => notYetImplementedFactory('Array.flat()'),
      'flatMap' => notYetImplementedFactory('Array.flatMap()'),
      'forEach' => notYetImplementedFactory('Array.forEach()'),
      'includes' => notYetImplementedFactory('Array.includes()'),
      'indexOf' => notYetImplementedFactory('Array.indexOf()'),
      'join' => notYetImplementedFactory('Array.join()'),
      'keys' => notYetImplementedFactory('Array.keys()'),
      'lastIndexOf' => notYetImplementedFactory('Array.lastIndexOf()'),
      'length' => 0,
      'map' => notYetImplementedFactory('Array.map()'),
      'pop' => notYetImplementedFactory('Array.pop()'),
      'push' => notYetImplementedFactory('Array.push()'),
      'reduce' => notYetImplementedFactory('Array.reduce()'),
      'reduceRight' => notYetImplementedFactory('Array.reduceRight()'),
      'reverse' => notYetImplementedFactory('Array.reverse()'),
      'shift' => notYetImplementedFactory('Array.shift()'),
      'slice' => notYetImplementedFactory('Array.slice()'),
      'some' => notYetImplementedFactory('Array.some()'),
      'sort' => notYetImplementedFactory('Array.sort()'),
      'splice' => notYetImplementedFactory('Array.splice()'),
      'toLocaleString' => notYetImplementedFactory('Array.toLocaleString()'),
      'toString' => notYetImplementedFactory('Array.toString()'),
      'unshift' => notYetImplementedFactory('Array.unshift()'),
      'values' => notYetImplementedFactory('Array.values()'),
      'Symbol(Symbol.iterator)' => notYetImplementedFactory('Array.values()'),
      'Symbol(Symbol.unscopables)' => [
        'at' => true,
        'copyWithin' => true,
        'entries' => true,
        'fill' => true,
        'find' => true,
        'findIndex' => true,
        'findLast' => true,
        'findLastIndex' => true,
        'flat' => true,
        'flatMap' => true,
        'includes' => true,
        'keys' => true,
        'values' => true,
      ],
      '__proto__' => Scope::Global()->Object->prototype,
    ]),
    'Symbol(Symbol.species)' => new Resolvable(fn () => Scope::Global()->Array),
    'Symbol(Symbol.species)' => new Resolvable(fn () => Scope::Global()->Array),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Function->prototype),
  ]
);

Scope::Global()->const->Function = new Instance(
  function (FunctionExpression|Instance $init) {
    $value = Instance::valueOf($init);
    $scope = Instance::scopeOf($this);
    $prototype = $this->prototype;

    return new Instance(
      $value,
      $scope,
      [
        'name' => $init->name,
        'length' => $init->length,
        '__proto__' => $prototype,
      ],
    );
  },
  Scope::Global(),
  [
    'length' => 1,
    'name' => 'Function',
    'prototype' => new Resolvable(fn () => new Instance(
      null,
      Scope::Global(),
      [
        'apply' => notYetImplementedFactory('().apply'),
        'bind' => notYetImplementedFactory('().bind'),
        'call' => notYetImplementedFactory('().call'),
        'constructor' => new Resolvable(fn () => Scope::Global()->Function),
        'length' => 0,
        'name' => '',
        'toString' => notYetImplementedFactory('().toString'),
        'Symbol(Symbol.hasInstance)' => notYetImplementedFactory('().Symbol(Symbol.hasInstance)'),
        new Getters([
          'arguments' => throwStrictModeFactory(),
          'caller' => throwStrictModeFactory(),
        ]),
        new Setters([
          'arguments' => throwStrictModeFactory(),
          'caller' => throwStrictModeFactory(),
        ]),
        '__proto__' => new Resolvable(fn () => Scope::Global()->Object->prototype),
      ],
    )),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Object->prototype),
  ],
);

Scope::Global()->const->Boolean = new Instance(
  function (bool|BooleanLiteral|Instance $value) {
    $value = Instance::valueOf($value);
    $scope = Instance::scopeOf($this);
    $prototype = $this->prototype;

    return new Instance(
      $value,
      $scope,
      ['__proto__' => $prototype],
    );
  },
  Scope::Global(),
  [
    'length' => 1,
    'name' => 'Boolean',
    'prototype' => new Resolvable(fn () => new Instance(false, Scope::Global(), [
      'constructor' => new Resolvable(fn () => Scope::Global()->Boolean),
      'toString' => notYetImplementedFactory('Boolean.prototype.toString()'),
      'valueOf' => notYetImplementedFactory('Boolean.prototype.valueOf()'),
      '__proto__' => Scope::Global()->Object->prototype,
    ])),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Function->prototype),
  ],
);

Scope::Global()->const->Number = new Instance(
  function (int|float|NumberLiteral|Instance $value) {
    $value = Instance::valueOf($value);
    $scope = Instance::scopeOf($this);
    $prototype = $this->prototype;

    return new Instance(
      $value,
      $scope,
      ['__proto__' => $prototype],
    );
  },
  Scope::Global(),
  [
    'EPSILON' => 2.220446049250313e-16,
    'MAX_SAFE_INTEGER' => 9007199254740991,
    'MAX_VALUE' => 1.7976931348623157e+308,
    'MIN_SAFE_INTEGER' => -9007199254740991,
    'MIN_VALUE' => 5e-324,
    'NEGATIVE_INFINITY' => new Resolvable(fn () => Literal::NegativeInfinity()),
    'NaN' => new Resolvable(fn () => Literal::NaN()),
    'POSITIVE_INFINITY' => new Resolvable(fn () => Literal::PositiveInfinity()),
    'isFinite' => notYetImplementedFactory('Number.isFinite()'),
    'isInteger' => notYetImplementedFactory('Number.isInteger()'),
    'isNaN' => notYetImplementedFactory('Number.isNaN()'),
    'isSafeInteger' => notYetImplementedFactory('Number.isSafeInteger()'),
    'length' => 1,
    'name' => 'Number',
    'parseFloat' => notYetImplementedFactory('Number.parseFloat()'),
    'parseInt' => notYetImplementedFactory('Number.parseInt()'),
    'prototype' => new Resolvable(fn () => new Instance(0, Scope::Global(), [
      'constructor' => new Resolvable(fn () => Scope::Global()->Number),
      'toExponential' => notYetImplementedFactory('Number.prototype.toExponential()'),
      'toFixed' => notYetImplementedFactory('Number.prototype.toFixed()'),
      'toLocaleString' => notYetImplementedFactory('Number.prototype.toLocaleString()'),
      'toPrecision' => notYetImplementedFactory('Number.prototype.toPrecision()'),
      'toString' => notYetImplementedFactory('Number.prototype.toString()'),
      'valueOf' => notYetImplementedFactory('Number.prototype.valueOf()'),
      '__proto__' => Scope::Global()->Object->prototype,
    ])),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Function->prototype),
  ],
);

Scope::Global()->const->String = new Instance(
  function (string|StringLiteral|Instance $value) {
    $value = Instance::valueOf($value);
    $scope = Instance::scopeOf($this);
    $prototype = $this->prototype;

    return new Instance(
      $value,
      $scope,
      [
        'length' => strlen($value),
        '__proto__' => $prototype,
      ],
    );
  },
  Scope::Global(),
  [
    'fromCharCode' => notYetImplementedFactory('String.fromCharCode()'),
    'fromCodePoint' => notYetImplementedFactory('String.fromCodePoint()'),
    'length' => 1,
    'name' => 'String',
    'prototype' => new Resolvable(fn () => new Instance('', Scope::Global(), [
      'anchor' => notYetImplementedFactory('String.anchor()'),
      'at' => notYetImplementedFactory('String.at()'),
      'big' => notYetImplementedFactory('String.big()'),
      'blink' => notYetImplementedFactory('String.blink()'),
      'bold' => notYetImplementedFactory('String.bold()'),
      'charAt' => notYetImplementedFactory('String.charAt()'),
      'charCodeAt' => notYetImplementedFactory('String.charCodeAt()'),
      'codePointAt' => notYetImplementedFactory('String.codePointAt()'),
      'concat' => notYetImplementedFactory('String.concat()'),
      'constructor' => new Resolvable(fn () => Scope::Global()->String),
      'endsWith' => notYetImplementedFactory('String.endsWith()'),
      'fixed' => notYetImplementedFactory('String.fixed()'),
      'fontcolor' => notYetImplementedFactory('String.fontcolor()'),
      'fontsize' => notYetImplementedFactory('String.fontsize()'),
      'includes' => notYetImplementedFactory('String.includes()'),
      'indexOf' => notYetImplementedFactory('String.indexOf()'),
      'italics' => notYetImplementedFactory('String.italics()'),
      'lastIndexOf' => notYetImplementedFactory('String.lastIndexOf()'),
      'length' => 0,
      'link' => notYetImplementedFactory('String.link()'),
      'localeCompare' => notYetImplementedFactory('String.localeCompare()'),
      'match' => notYetImplementedFactory('String.match()'),
      'matchAll' => notYetImplementedFactory('String.matchAll()'),
      'normalize' => notYetImplementedFactory('String.normalize()'),
      'padEnd' => notYetImplementedFactory('String.padEnd()'),
      'padStart' => notYetImplementedFactory('String.padStart()'),
      'repeat' => notYetImplementedFactory('String.repeat()'),
      'replace' => notYetImplementedFactory('String.replace()'),
      'replaceAll' => notYetImplementedFactory('String.replaceAll()'),
      'search' => notYetImplementedFactory('String.search()'),
      'slice' => notYetImplementedFactory('String.slice()'),
      'small' => notYetImplementedFactory('String.small()'),
      'split' => notYetImplementedFactory('String.split()'),
      'startsWith' => notYetImplementedFactory('String.startsWith()'),
      'strike' => notYetImplementedFactory('String.strike()'),
      'sub' => notYetImplementedFactory('String.sub()'),
      'substr' => notYetImplementedFactory('String.substr()'),
      'substring' => notYetImplementedFactory('String.substring()'),
      'sup' => notYetImplementedFactory('String.sup()'),
      'toLocaleLowerCase' => notYetImplementedFactory('String.toLocaleLowerCase()'),
      'toLocaleUpperCase' => notYetImplementedFactory('String.toLocaleUpperCase()'),
      'toLowerCase' => notYetImplementedFactory('String.toLowerCase()'),
      'toString' => notYetImplementedFactory('String.toString()'),
      'toUpperCase' => notYetImplementedFactory('String.toUpperCase()'),
      'trim' => function () {
        $value = Instance::valueOf($this);
        return Literal::String(trim($value));
      },
      'trimEnd' => notYetImplementedFactory('String.trimEnd()'),
      'trimLeft' => notYetImplementedFactory('String.trimStart()'),
      'trimRight' => notYetImplementedFactory('String.trimEnd()'),
      'trimStart' => notYetImplementedFactory('String.trimStart()'),
      'valueOf' => notYetImplementedFactory('String.valueOf()'),
      'Symbol(Symbol.iterator)' => notYetImplementedFactory('String.[Symbol.iterator]()'),
      new Setters([
        'length' => fn ($value) => $value,
      ]),
      '__proto__' => Scope::Global()->Object->prototype,
    ])),
    'raw' => notYetImplementedFactory('String.raw()'),
    '__proto__' => new Resolvable(fn () => Scope::Global()->Function->prototype),
  ],
);

Scope::Global()->const->console = new Resolvable(fn () => new Instance(
  null,
  Scope::Global(),
  import('./console'),
));

return Scope::Global();