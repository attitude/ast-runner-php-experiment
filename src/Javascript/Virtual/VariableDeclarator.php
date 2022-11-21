<?php

namespace Javascript;

final class VariableDeclarator extends \ArrayObject {
  public readonly Identifier|Literal|Instance|null $init;

  public function __construct(
    public readonly Identifier $id,
    string|int|float|bool|MemberExpression|Identifier|Literal|Instance|null $init,
  ) {
    if ($init instanceof MemberExpression) {
      $this->init = MemberExpression::valueOf($init);
    } else if (is_string($init)) {
      $this->init = Literal::String($init);
    } else if (is_bool($init)) {
      $this->init = Literal::Boolean($init);
    } else if (is_int($init) || is_float($init)) {
      $this->init = Literal::Number($init);
    } else {
      $this->init = $init;
    }

    parent::__construct(['id' => $this->id, 'init' => $this->init]);
  }
}