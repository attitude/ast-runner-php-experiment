<?php

namespace Javascript;

final class MemberExpression extends \ArrayObject {
  public function __construct(
    private string $property,
    private Literal|Instance $object,
  ) {
    parent::__construct([$property, $object]);
  }

  public static function valueOf(MemberExpression $instance) {
    return $instance->object->{$instance->property};
  }
}