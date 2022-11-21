<?php

namespace Javascript;

use stdClass;

abstract class Interpreter {
  public function dumpLoc(stdClass $node, Scope $scope) {
    $code = Scope::moduleOf($scope)->code;
    $underlineStyle = 'color:black; border-bottom:2px dashed red; font-weight:bold;';

    echo '<pre style="color: grey;">';
    for (
      $line = max(0, $node->loc->start->line - 2);
      $line < min(count($code) - 1, $node->loc->end->line + 1);
      $line++
    ) {
      $currentLine = $line + 1;

      if ($currentLine >= $node->loc->start->line && $currentLine <= $node->loc->end->line) {
        echo '<span style="color:black; font-weight:bold;">' . $currentLine . ':</span> ';
      } else {
        echo '<span>' . $currentLine . ':</span> ';
      }

      echo '<code>';
      if ($currentLine === $node->loc->start->line) {
        echo htmlspecialchars(substr($code[$line], 0, $node->loc->start->column));
        echo '<span style="' . $underlineStyle . '">' . htmlspecialchars(substr(
          $code[$line],
          $node->loc->start->column,
          $node->loc->start->line === $node->loc->end->line ? $node->loc->end->column - $node->loc->start->column : null
        )) . '</span>';

        if ($node->loc->start->line === $node->loc->end->line) {
          echo htmlspecialchars(substr($code[$line], $node->loc->end->column));
        }
      } else if ($currentLine === $node->loc->end->line) {
        echo '<span style="' . $underlineStyle . '">' . htmlspecialchars(substr($code[$line], 0, $node->loc->end->column)) . '</span>';
        echo htmlspecialchars(substr($code[$line], $node->loc->end->column));
      } else if ($currentLine > $node->loc->start->line && $currentLine < $node->loc->end->line) {
        echo '<span style="' . $underlineStyle . '">' . htmlspecialchars($code[$line]) . '</span>';
      } else {
        echo htmlspecialchars($code[$line]);
      }

      echo '</code>' . "\n";
    }
    echo '</pre>';
  }

  public function rawValueOf(Scope $scope, $value) {
    $type = gettype($value);

    switch ($type) {
      case 'array':
        foreach ($value as &$member) {
          $member = $this->rawValueOf($scope, $member);
        }

        return $value;
        break;
      case 'object':
        if ($value instanceof Identifier) {
          return $this->rawValueOf($scope, $scope->{$value});
        } else if ($value instanceof Literal || $value instanceof Instance) {
          return $this->rawValueOf($scope, Instance::valueOf($value));
        }
        break;
      case 'string':
      case 'NULL':
        return $value;
        break;
    }

    throwUnexpected('$value', [
      '$value' => $value,
      '$type' => $type,
    ]);
  }

  public function eval(Scope $scope, $node = null, ...$rest) {
    static $xpath = [];

    $xpath[] = ($node->type ?? json_encode($node)) . ':' . (isset($node->name) && is_string($node->name) ? $node->name : '');

    memory_set_usage(implode('/', $xpath) . '::start');

    if (VERBOSE_NODE_EVAL) {
      if ($node instanceof stdClass) {
        echo "<pre>Eval: '{$node->type}'</pre>";
        $this->dumpLoc($node, $scope);
      } else {
        echo "<pre>Eval: " . json_encode($node) . "</pre>";
      }
    }

    if ($node instanceof stdClass) {
      if (method_exists($this, $node->type)) {
        $return = $this->{$node->type}($scope, $node, ...$rest);
        array_pop($xpath);

        return $return;
      } else {
        $this->dumpLoc($node, $scope);

        return new \Exception("Not implemented node '{$node->type}'");
      }
    } else if (is_array($node)) {
      $result = [];

      foreach ($node as $item) {
        $result[] = $this->eval($scope, $item);
      }

      memory_set_usage(implode('/', $xpath) . ':end');
      array_pop($xpath);

      return $result;
    } else {
      memory_set_usage(implode('/', $xpath) . ':end');
      array_pop($xpath);

      return $node;
    }
  }
}
