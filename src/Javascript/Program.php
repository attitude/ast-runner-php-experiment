<?php

namespace Javascript;

use stdClass;

if (!defined('VERBOSE_NODE_EVAL')) {
  define('VERBOSE_NODE_EVAL', false);
}

// FunctionDeclaration[?Yield, ?Await, ?Default]
// GeneratorDeclaration[?Yield, ?Await, ?Default]
// AsyncFunctionDeclaration[?Yield, ?Await, ?Default]
// AsyncGeneratorDeclaration[?Yield, ?Await, ?Default]
class Program extends Interpreter {
  public function __construct(private ?Loaders $loaders = null) {
  }

  public function import(...$arguments) {
    memory_set_usage("Program->import()");

    $scope = maybe(fn () => array_find($arguments, isScope), isUndefinedException);
    $module = array_find($arguments, isString);

    memory_set_usage("Program->import({$module})->load");
    $Module = $this->loaders->load(
      $scope && substr($module, 0, 1) === '.'
        ? (substr($module, 0, 2) === '..'
          ? dirname(dirname(Scope::moduleOf($scope)->path)) . substr($module, 2)
          : dirname(Scope::moduleOf($scope)->path) . substr($module, 1)
        )
        : $module
    );
    memory_set_usage("Program->import({$module})->loaded");

    return $this->run($Module);
  }

  protected function hoistHoistableDeclarations(array $body) {
    $start = [];
    $hoisted = [];
    $rest = [];

    $variableDeclarationFound = false;

    foreach ($body as &$node) {
      $type = $node->type;

      if ($type === 'VariableDeclaration') {
        $variableDeclarationFound = true;
      }

      if (in_array($type, [
        'FunctionDeclaration',
        'GeneratorDeclaration',
        'AsyncFunctionDeclaration',
        'AsyncGeneratorDeclaration',
      ])) {
        $hoisted[] = $node;
      } else if (!$variableDeclarationFound) {
        $start[] = $node;
      } else {
        $rest[] = $node;
      }
    }

    return [
      ...$start,
      ...$hoisted,
      ...$rest,
    ];
  }

  public function run(Module $module) {
    if ($module->ast->sourceType === 'module') {
      memory_set_usage("Program->run({$module->module})::start");
      $scope = new Scope($module);
      $scope->declare(Scope::CONST, 'exports', Instance::Object($scope));

      foreach ($this->hoistHoistableDeclarations($module->ast->body) as $node) {
        if ($node->type === 'ReturnStatement') {
          throw new \Exception("A 'return' statement can only be used within a function body.");
        }

        $this->eval($scope, $node);
      }

      memory_set_usage("Program->run({$module->module})::end");

      return $scope->exports;
    } else {
      throw new \Exception("Unsupported source type: '{$this->program->sourceType}'");
    }
  }

  protected function ExportAllDeclaration(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'source' => Expect::type('object'),
        'exported' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $source = (string) expectStringLiteral($this->eval($scope, $node->source));
    $exported = $this->eval($scope, $node->exported);
    $exports = $this->import($scope, $source);

    if ($exported instanceof Identifier) {
      $scope->exports->{$exported} = $exports;
    } else {
      foreach (Instance::valueOf($exports) as $exported => $exportedValue) {
        $scope->exports->{$exported} = $exportedValue;
      }
    }
  }

  protected function ExportNamedDeclaration(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'declaration' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'specifiers' => Expect::type('array'),
        'source' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'exportKind?' => Expect::literal('value'), // NOTE: Optionality by Peast
        'assertions?' => Expect::type('array')->andCount(0), // NOTE: Optionality by Peast
        'loc' => Expect::type('object'),
      ], $node);
    }

    $declaration = $this->eval($scope, $node->declaration);
    $source = $this->eval($scope, $node->source);

    if ($declaration === null) {
      if ($source instanceof StringLiteral) {
        $source = (string) $source;

        $exports = $this->import($scope, $source);

        foreach ($node->specifiers as $specifier) {
          $specifier = $this->eval($scope, $specifier);

          if ($specifier instanceof ExportSpecifier) {
            $scope->exports->{$specifier->exported} = $exports->{$specifier->local};
          }
        }
      } else {
        throwUnexpected('$source', $source);
      }
    } else {
      if ($source === null) {
        if ($declaration instanceof Identifier) {
          $scope->exports->{$declaration} = $scope->{$declaration};
        } else {
          foreach (expectArray($declaration) as $key => $value) {
            if (is_int($key)) {
              if ($value instanceof Identifier) {
                $scope->exports->{$value} = $scope->{$value};
              } else {
                throwUnexpected('$value', $value);
              }
            } else {
              dump(['$key' => $key, '$value' => $value], true);
              exit;
            }
          }
        }
      } else {
        throwUnexpected('$source', $source);
      }
    }
  }

  protected function ImportDeclaration(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'source' => Expect::type('object'),
        'specifiers' => Expect::type('array'),
        'importKind?' => Expect::literal('value'), // NOTE: Optionality by Peast
        'assertions?' => Expect::type('array'), // NOTE: Optionality by Peast
        'loc' => Expect::type('object'),
      ], $node);
    }

    $source = $this->eval($scope, $node->source);

    if ($source instanceof StringLiteral) {
      $source = Literal::valueOf($source);

      foreach ($node->specifiers as $specifier) {
        $specifier = $this->eval($scope, $specifier);

        if ($specifier instanceof ImportSpecifier) {
          $scope->const->{$specifier->local} = new Resolvable(function () use ($scope, $node, $source, $specifier) {
            $imported = $this->import($scope, $source);

            if (is_array($imported)) {
              return $imported[(string) $specifier->imported];
            } else {
              dump([
                'imported' => $scope->{$specifier->local},
                '$imported' => $imported,
                '$source' => $source,
                '$specifier' => $specifier,
                '$node' => $node,
              ], true);

              throwUnexpected('$imported', $imported);
            }
          });
        } else {
          throwUnexpected('$specifier', $specifier);
        }
      }

      // $specifiers = array_map(fn ($specifier) => $this->eval($scope, $specifier), $node->specifiers);

    } else {
      throwUnexpected('$source', $source);
    }
  }

  protected function ImportDefaultSpecifier(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'local' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return new ImportSpecifier(
      $this->eval($scope, $node->local),
    );
  }

  protected function ExportSpecifier(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'local' => Expect::type('object'),
        'exported' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return new ExportSpecifier(
      $this->eval($scope, $node->local),
      $this->eval($scope, $node->exported),
    );

    dump($node, true);
    exit;
  }

  protected function ImportSpecifier(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'local' => Expect::type('object'),
        'imported' => Expect::type('object'),
        'importKind?' => Expect::literal('value'), // NOTE: Optionality by Peast
        'loc' => Expect::type('object'),
      ], $node);
    }

    return new ImportSpecifier(
      $this->eval($scope, $node->local),
      $this->eval($scope, $node->imported),
    );
  }

  protected function TSTypeAliasDeclaration(Scope $scope, stdClass $node) {
  }

  protected function ExpressionStatement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'expression' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return $this->eval($scope, $node->expression);
  }

  protected function FunctionDeclaration(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'id' => Expect::type('object'),
        'generator' => Expect::false(),
        'expression' => Expect::false(),
        'async' => Expect::false(),
        'params' => Expect::type('array'),
        'body' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $id = $this->eval($scope, $node->id);
    $init = $this->__Function($scope, $node);

    return $scope->declare('const', $id, $init);
  }

  protected function FunctionExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'id' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'generator' => Expect::false(),
        'expression' => Expect::false(),
        'async' => Expect::false(),
        'params' => Expect::type('array'),
        'body' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return $this->__Function($scope, $node);
  }

  protected function ArrowFunctionExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'generator' => Expect::false(),
        'id' => Expect::null(),
        'params' => Expect::type('array'),
        'body' => Expect::type('object'),
        'async' => Expect::false(),
        'expression' => Expect::false(),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return $this->__Function($scope, $node);
  }

  protected function __Function(Scope $parentScope, stdClass $node) {
    // TODO:
    // $generator = $this->eval($parentScope, $node->generator);
    $id = $this->eval($parentScope, $node->id);
    // $async = $this->eval($parentScope, $node->async);
    // $expression = $this->eval($parentScope, $node->expression);

    $program = $this;

    $closure = function (...$arguments) use ($program, $parentScope, $node) {
      $scope = new Scope($parentScope);

      foreach ($node->params as $index => $param) {
        $param = $program->eval($scope, $param, $arguments[$index]);

        foreach (items($param) as $param) {
          if ($param instanceof Identifier) {
            $param = new VariableDeclarator(
              $param,
              $arguments[$index],
            );
          }

          if ($param instanceof VariableDeclarator) {
            $scope->declare('let', $param->id);
            $scope->{$param->id} = $param->init;
          } else {
            throwUnexpected('$param', $param);
          }
        }
      }

      return $program->eval($scope, $node->body);
    };

    $name = $id ?? '';
    $length = count($node->params);

    return Instance::Function(
      $parentScope,
      new FunctionExpression(
        $closure,
        $name,
        $length,
      ),
    );
  }

  protected function JSXFragment(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'openingFragment' => Expect::type('object'),
        'closingFragment' => Expect::type('object'),
        'children' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $this->eval($scope, $node->openingFragment);
    $this->eval($scope, $node->closingFragment);

    return implode("\n", array_map(
      fn ($child) => $this->eval($scope, $child),
      $node->children,
    ));
  }

  protected function JSXOpeningFragment(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'loc' => Expect::type('object'),
      ], $node);
    }
  }

  protected function JSXClosingFragment(Scope $scope, stdClass $node): void {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'loc' => Expect::type('object'),
      ], $node);
    }
  }

  protected function JSXText(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'value' => Expect::type('string'),
        'raw' => Expect::type('string'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return Literal::String($node->value);
  }

  const EMPTY_ELEMENTS = [
    'area', 'base',  'br',     'col',    'embed',
    'hr',   'img',   'input',  'keygen', 'link',
    'meta', 'param', 'source', 'track',  'wbr',
  ];

  protected function JSXSpreadAttribute(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'argument' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $argument = $this->eval($scope, $node->argument);

    if ($argument instanceof Identifier) {
      return $scope->{$argument};
    } else {
      throwUnexpected('$argument', $argument);
    }
  }

  protected function JSXElement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'openingElement' => Expect::type('object'),
        'closingElement' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'children' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $this->eval($scope, $node->closingElement);

    ['name' => $name, 'attributes' => $attributes] = $this->eval($scope, $node->openingElement);

    if (!$node->openingElement->selfClosing) {
      $children = [];

      foreach ($node->children as $child) {
        $_child = $this->eval($scope, $child);

        $children[] = $this->rawValueOf($scope, $_child);
      }

      if ($children instanceof Instance) {
        if (Instance::prototypeOf($children)->name === 'Array') {
          $children = Instance::valueOf($children);
        } else {
          throwUnexpected('$children.prototype.name', Instance::valueOf($children)->name);
        }
      } else if (!is_array($children)) {
        throwUnexpected('$children', $children);
      }

      $attributes['children'] = $children;
    }

    if ($name instanceof Identifier) {
      $tag = (string) $name;

      if (strtolower($tag) === $tag) {
        $array_attributes = array_except($attributes, 'children');
        $children = array_flatten(array_pick($attributes, 'children'));

        return "<{$tag}" . (implode('', array_map(
          fn ($key, $value) => " {$key}=" . ($key === 'style'
            ? implode(';', array_map(
              fn ($key, $value) => "{$key}:{$value}",
              array_map(
                fn (string $key) => preg_replace_callback('/[A-Z]/', fn ($char) => '-' . strtolower($char[0]), $key),
                array_keys((array) $value),
              ),
              array_values((array) $value),
            ))
            : implode(' ', items($value))
          ),
          array_keys($array_attributes),
          array_values($array_attributes)
        ))) . (in_array($tag, self::EMPTY_ELEMENTS)
          ? ' />'
          : '>' . implode('', $children) . "</{$tag}>"
        );
      } else {
        $tag = $scope->{$name};
        $closure = Instance::valueOf($tag);

        return $closure(Instance::Object($scope, new ObjectExpression($attributes)));
      }
    } else {
      throwUnexpected('$name', $name);
    }
  }

  protected function JSXOpeningElement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'selfClosing' => Expect::type('bool'),
        'name' => Expect::type('object'),
        'attributes' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $name = $this->eval($scope, $node->name);
    $attributes = [];

    foreach ($node->attributes as $attribute) {
      $attributeType = $attribute->type;

      if ($attributeType === 'JSXSpreadAttribute') {
        $spread = $this->eval($scope, $attribute);

        if ($spread instanceof Instance) {
          foreach (Instance::valueOf($spread) as $k => $v) {
            $attributes[$k] = $v;
          }
        } else {
          throwUnexpected('$spread', $spread);
        }
      } else if ($attributeType = 'JSXAttribute') {
        $_attribute = $this->eval($scope, $attribute);

        if ($_attribute instanceof Entry) {
          $attributes[$_attribute->key] = $_attribute->value;
        } else {
          throwUnexpected('$_attribute', $_attribute);
        }
      } else {
        throwUnexpected('$attribute', $attribute);
      }
    }

    return [
      'name' => $name,
      'attributes' => $attributes,
    ];
  }

  protected function JSXExpressionContainer(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'expression' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return $this->eval($scope, $node->expression);
  }

  protected function JSXAttribute(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'name' => Expect::type('object'),
        'value' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $name = $this->eval($scope, $node->name);
    $value = $this->eval($scope, $node->value);

    if ($value instanceof Identifier) {
      $value = $scope->{$value};
    }

    return new Entry(
      $name,
      $value,
    );

    dump($node, true);
    exit;
  }

  protected function JSXClosingElement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'name' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }
  }

  protected function JSXIdentifier(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'name' => Expect::type('string'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return new Identifier($node->name);
  }

  protected function BlockStatement(Scope $parentScope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'body' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $scope = new Scope($parentScope);

    foreach ($this->hoistHoistableDeclarations($node->body) as $step) {
      if ($step->type === 'ReturnStatement') {
        return $this->eval($scope, $step);
      } else {
        $this->eval($scope, $step);
      }
    }

    unset($scope);

    return $parentScope->undefined;
  }

  protected function CallExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'callee' => Expect::type('object'),
        'arguments' => Expect::type('array'),
        'optional' => Expect::type('bool'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $optional = $this->eval($scope, $node->optional);

    if ($optional !== false) {
      throwNotYetImplemented('optional property');
    }

    $callee = $this->eval($scope, $node->callee);

    if ($callee instanceof Identifier) {
      $callee = $scope->{$callee};
    }

    $arguments = array_map(fn ($argument) => $this->eval($scope, $argument), $node->arguments);
    $arguments = array_map(fn ($argument) => $argument instanceof Identifier ? $scope->{$argument} : $argument, $arguments);

    $object = null;

    if ($callee instanceof MemberExpression) {
      [$member, $object] = $callee;
      $callee = $object->{$member};

      if (!($object instanceof Instance || $object instanceof Literal)) {
        throwUnexpected('type of 2nd destructured member', $object);
      }
    }

    if ($callee instanceof Instance) {
      $callee = Instance::valueOf($callee);
    }

    if ($object) {
      return ($callee->bindTo($object, $object))(...$arguments);
    } else {
      return $callee(...$arguments);
    }
  }

  protected function ReturnStatement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'argument' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $argument = $this->eval($scope, $node->argument);

    return $argument;
  }

  protected function ConditionalExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'test' => Expect::type('object'),
        'consequent' => Expect::type('object'),
        'alternate' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $test = $this->eval($scope, $node->test);

    if ($test instanceof Identifier) {
      $test = $scope->{$test};
    }

    return Instance::valueOf($test)
      ? $this->eval($scope, $node->consequent)
      : $this->eval($scope, $node->alternate);
  }

  protected function LogicalExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'operator' => Expect::memberOf(['||', '&&']),
        'left' => Expect::type('object'),
        'right' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    if ($node->operator === '||') {
      $left = $this->eval($scope, $node->left);

      if ($left instanceof Identifier) {
        $left = $scope->{$left};
      }

      $valueLeft = Instance::valueOf($left);

      if ($valueLeft !== null) {
        return $left;
      } else {
        $right = $this->eval($scope, $node->right);

        return $right instanceof Identifier ? $scope->{$right} : $right;
      }
    } else if ($node->operator == '&&') {
      $left = $this->eval($scope, $node->left);

      if ($left instanceof MemberExpression) {
        [$property, $left] = $left;
        $left = $left->{$property};
      } else if ($left instanceof Identifier) {
        $left = $scope->{$left};
      }

      if (!Instance::valueOf($left)) {
        return $left;
      }

      $right = $this->eval($scope, $node->right);

      if ($right instanceof MemberExpression) {
        [$property, $right] = $right;
        $right = $right->{$property};
      } else if ($right instanceof Identifier) {
        $right = $scope->{$right};
      }

      return $right;
    }
  }

  protected function UnaryExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'operator' => Expect::memberOf(['typeof']),
        'prefix' => Expect::true(),
        'argument' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $argument = $this->eval($scope, $node->argument);

    if ($argument instanceof Identifier) {
      $argument = $scope->{$argument};
    }

    if ($node->operator === 'typeof') {
      if ($argument instanceof NullLiteral) {
        return Literal::String('object');
      } else if ($argument instanceof NumberLiteral) {
        return Literal::String('number');
      } else if ($argument instanceof BooleanLiteral) {
        return Literal::String('boolean');
      } else if ($argument instanceof StringLiteral) {
        return Literal::String('string');
      } else if ($argument instanceof Instance) {
        return Literal::String('object');
      } else {
        throwUnexpected('$argument', $argument);
      }
    } else {
      throwUnexpected('$node->argument', $argument);
    }
  }

  protected function BinaryExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'operator' => Expect::memberOf(['+', '*', '**', '/', '==', '===']),
        'left' => Expect::type('object'),
        'right' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $right = $this->eval($scope, $node->right);

    if ($right instanceof MemberExpression) {
      $right = MemberExpression::valueOf($right);
    }

    if ($right instanceof Identifier) {
      $right = $scope->{$right};
    }

    $left = $this->eval($scope, $node->left);

    if ($left instanceof Identifier) {
      $left = $scope->{$left};
    }

    if ($left instanceof MemberExpression) {
      $left = MemberExpression::valueOf($left);
    }

    if ($left instanceof Literal && $right instanceof Literal) {
      if ($node->operator === '==') {
        return Literal::Boolean(
          Literal::valueOf($left) == Literal::valueOf($right)
        );
      } else if ($node->operator === '===') {
        return Literal::Boolean(
          Literal::valueOf($left) === Literal::valueOf($right)
        );
      } else if ($node->operator === '*') {
        try {
          return Literal::number(
            Literal::valueOf($left) * Literal::valueOf($right)
          );
        } catch (\TypeError $th) {
          return Scope::Global()->NaN;
        }
      } else if ($node->operator === '/') {
        try {
          return Literal::number(
            Literal::valueOf($left) / Literal::valueOf($right)
          );
        } catch (\TypeError $th) {
          return Scope::Global()->NaN;
        }
      } else if ($node->operator === '**') {
        try {
          return Literal::number(
            Literal::valueOf($left) ** Literal::valueOf($right)
          );
        } catch (\TypeError $th) {
          return Scope::Global()->NaN;
        }
      } else if ($node->operator === '+') {
        if ($left instanceof StringLiteral || $right instanceof StringLiteral) {
          return Literal::String(
            Literal::valueOf($left) . Literal::valueOf($right)
          );
        } else {
          try {
            return Literal::number(
              Literal::valueOf($left) + Literal::valueOf($right)
            );
          } catch (\TypeError $th) {
            return Scope::Global()->NaN;
          }
        }
      } else {
        throwNotYetImplemented("operation: {$left} {$node->operator} {$right}");
      }
    } else if ($left instanceof Instance && $right instanceof Instance) {
      dump([
        '$left' => $left,
        '$node->operator' => $node->operator,
        '$right' => $right,
      ]);

      throwNotYetImplemented("operation: {$left} {$node->operator} {$right}");
    }

    throwUnexpected("Only Instances of left & right are implemented", [
      '$right' => $right,
      '$left' => $left,
    ]);
  }

  protected function MemberExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'object' => Expect::type('object'),
        'property' => Expect::type('object'),
        'computed' => Expect::type('bool'),
        'optional' => Expect::type('bool'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $computed = $this->eval($scope, $node->computed);
    $optional = $this->eval($scope, $node->optional);

    if ($computed !== false) {
      throwNotYetImplemented('computed property');
    }

    if ($optional !== false) {
      throwNotYetImplemented('optional property');
    }

    $object = $this->eval($scope, $node->object);
    $property = $this->eval($scope, $node->property);

    if ($object instanceof Identifier) {
      $object = $scope->{$object};
    }

    return new MemberExpression($property, $object);
  }

  protected function ArrayExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'elements' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    return Instance::Object(
      $scope,
      new ArrayExpression(
        array_map(fn ($element) => $this->eval($scope, $element), $node->elements),
      ),
    );
  }

  protected function ObjectExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'properties' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $object = [];

    foreach ($node->properties as $property) {
      ['key' => $key, 'value' => $value] = $this->eval($scope, $property);

      if ($value instanceof Identifier) {
        $value = $scope->{$value};
      }

      if ($key instanceof Identifier) {
        $object["{$key}"] = $value;
      } else if ($key instanceof NumberLiteral) {
        $object[NumberLiteral::valueOf($key)] = $value;
      } else {
        throwUnexpected("key type", $key);
      }
    }

    return Instance::Object($scope, new ObjectExpression($object));
  }

  protected function RestElement(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'argument' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    if ($node->argument->type === 'Identifier') {
      return $this->eval($scope, $node->argument);
    } else {
      throwUnexpected('$node->argument', $node->argument);
    }
  }

  protected function Property(Scope $scope, stdClass $node, Literal|Instance $init = null) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'key' => Expect::type('object'),
        'value' => Expect::type('object'),
        'computed' => Expect::type('bool'),
        'method' => Expect::type('bool'),
        'shorthand' => Expect::type('bool'),
        'kind' => Expect::memberOf(['init']),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $computed = $this->eval($scope, $node->computed);

    if ($computed) {
      throwNotYetImplemented('computed property', $computed);
    }

    $method = $this->eval($scope, $node->method);

    if ($method) {
      throwNotYetImplemented("method property", $method);
    }

    $shorthand = $this->eval($scope, $node->shorthand);

    if ($shorthand) {
      $id = $this->eval($scope, $node->value, $init);

      if ($init) {
        $value = maybe(fn () => $init->{$id}, isUndefinedException) ?? $id->init;
      } else {
        $value = $scope->{$id};
      }
    } else {
      $value = $this->eval($scope, $node->value, $init);
    }

    $key = $this->eval($scope, $node->key);

    return ['key' => $key, 'value' => $value];
  }

  protected function NewExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'callee' => Expect::type('object'),
        'arguments' => Expect::type('array'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $callee = $this->eval($scope, $node->callee);
    $arguments = array_map(fn ($argument) => $this->eval($scope, $argument), $node->arguments);

    foreach ($arguments as &$argument) {
      if ($argument instanceof Identifier) {
        $argument = $scope->{$argument};
      }
    }

    $class = $scope->{$callee};
    $constructor = Instance::valueOf($class); // ($class)->constructor->bindTo($class);

    return ($constructor->bindTo($class, $class))(...$arguments);
  }

  protected function Literal(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'value' => Expect::literal(),
        'raw' => Expect::type('string'),
        'format?' => Expect::equal('single'), // NOTE: Peast key
        'loc' => Expect::type('object'),
      ], $node);
    }

    if ($node->raw === 'null') {
      return Literal::Null();
    } else if ($node->raw === 'false' || $node->raw === 'true') {
      return Literal::Boolean($node->value);
      // } else if (is_string($node->value)) {
      //   return Literal::String($node->value);
      // } else if (is_int($node->value)) {
      //   return Literal::Number($node->value);
      // } else if (is_float($node->value)) {
      //   return Literal::Number($node->value);
      // } else {
      //   dump($node);
      //   exit('Unfinished...');
    } else {
      return Literal::from($node->value);
    }
  }

  protected function AssignmentExpression(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'operator' => Expect::memberOf(['=']),
        'left' => Expect::type('object'),
        'right' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $right = $this->eval($scope, $node->right);
    $left = $this->eval($scope, $node->left);

    if ($left instanceof MemberExpression) {
      [$property, $left] = $left;

      return $left->{$property} = $right;;
    } else {
      throwUnexpected('AssignmentExpression left side', $left);
    }
  }

  protected function ObjectPattern(Scope $scope, stdClass $node, Instance|ObjectExpression $init) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'properties' => Expect::type('array'),
        'loc' => Expect::type('object'),
        'typeAnnotation?' => Expect::type('object'),
      ], $node);
    }

    $toDeclare = [];
    $keys = [];

    foreach ($node->properties as $property) {
      if ($property->type === 'Property') {
        $key = $this->eval($scope, $property->key);

        switch ($property->value->type) {
          case 'Identifier':
            $toDeclare[] = new VariableDeclarator(
              $this->eval($scope, $property->value),
              maybe(fn () => $init->{$key}, isUndefinedException) ?? Literal::Undefined(),
            );
            break;

          case 'AssignmentPattern':
            $toDeclare[] = new VariableDeclarator(
              $this->eval($scope, $property->value->left),
              maybe(fn () => $init->{$key}, isUndefinedException) ?? $this->eval($scope, $property->value->right),
            );
            break;

          case 'ObjectPattern':
            $toDeclare = array_merge($toDeclare, $this->eval($scope, $property->value, $init->{$key}));
            break;

          default:
            throwUnexpected('$property->value->type', $property->value->type, $property);
            break;
        }

        $keys[] = $key;
      } else if ($property->type === 'RestElement') {
        $identifier = $this->eval($scope, $property);
        $toDeclare[] = new VariableDeclarator(
          $identifier,
          Instance::Object(
            $scope,
            new ObjectExpression((array) Instance::valueOf($init, $keys)),
          ),
        );
      } else {
        throwUnexpected('$property', $property);
      }
    }

    return $toDeclare;
  }

  protected function AssignmentPattern(Scope $scope, stdClass $node, Literal|Instance|ObjectExpression $init = null) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'left' => Expect::type('object'),
        'right' => Expect::type('object'),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $leftType = $node->left->type;

    if ($leftType === 'Identifier') {
      $id = $this->eval($scope, $node->left);
      $right = $this->eval($scope, $node->right);

      return new VariableDeclarator(
        $id,
        $init ?? $right,
      );
    } else if ($leftType === 'ObjectPattern') {
      return $this->eval(
        $scope,
        $node->left,
        $init ?? $this->eval($scope, $node->right),
      );
    } else {
      throwUnexpected('$node->left', $node->left, $node);
    }
  }

  protected function VariableDeclaration(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'declarations' => Expect::type('array'),
        'kind' => Expect::memberOf(['let', 'const', 'var']),
        'loc' => Expect::type('object'),
      ], $node);
    }

    $declared = [];

    foreach ($node->declarations as $declaration) {
      $declarations = items($this->eval($scope, $declaration));

      foreach ($declarations as $declaration) {
        if ($declaration instanceof VariableDeclarator) {
          ['id' => $id, 'init' => $init] = $declaration;

          if ($init instanceof Identifier) {
            $init = $scope->{$init};
          }

          if ($init) {
            $declared[] = new Identifier($scope->declare($node->kind, $id, $init));
          } else {
            $declared[] = new Identifier($scope->declare($node->kind, $id));
          }
        } else {
          throwUnexpected('$declaration', $declaration);
        }
      }
    }

    return $declared;
  }

  protected function VariableDeclarator(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'id' => Expect::type('object'),
        'init' => Expect::union(
          Expect::null(),
          Expect::type('object'),
        ),
        'loc' => Expect::type('object'),
      ], $node);
    }

    if ($node->id->type === 'Identifier') {
      return new VariableDeclarator(
        $this->eval($scope, $node->id),
        $this->eval($scope, $node->init),
      );
    } else if ($node->id->type === 'ObjectPattern') {
      return $this->eval(
        $scope,
        $node->id,
        $this->eval($scope, $node->init),
      );
    } else {
      throwUnexpected('$id', $node->id);
    }
  }

  protected function Identifier(Scope $scope, stdClass $node) {
    if (ASSERT_AST_TREE) {
      Assert::equal((object) [
        'type' => Expect::equal(__FUNCTION__),
        'name' => Expect::type('string'),
        'rawName?' => Expect::type('string'), // NOTE: Peast
        'loc' => Expect::type('object'),
        'typeAnnotation?' => Expect::union(
          Expect::type('object'),
          Expect::null(),
        ),
      ], $node);
    }

    return new Identifier($node->name);
  }
}
