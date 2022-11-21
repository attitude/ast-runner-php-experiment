<?php

use Javascript\Instance;

const EMPTY_ELEMENTS = [
  'area', 'base',  'br',     'col',    'embed',
  'hr',   'img',   'input',  'keygen', 'link',
  'meta', 'param', 'source', 'track',  'wbr',
];

$jsx = function ($tag, $props) {
  $tag = Instance::valueOf($tag);

  if (is_string($tag)) {
    $_props = (array) Instance::valueOf($props);
    $attributes = array_except($_props, 'children');
    $children = array_flatten(array_pick($_props, 'children'));

    // dump(['$tag' => $tag, '$attributes' => $attributes]);

    return "<{$tag}" . (implode('', array_map(
      fn ($key, $value) => " {$key}=" . implode(' ', items($value)),
      array_keys($attributes),
      array_values($attributes)
    ))) . (in_array($tag, EMPTY_ELEMENTS)
      ? ' />'
      : '>' . implode("\n", array_map(
        fn ($child) => is_string($child) ? $child : throwUnexpected('$child', $child),
        $children,
      )) . "</{$tag}>"
    );

    notYetImplementedFactory("string '$tag'")();
  } else {
    return $tag($props);
  }
};

return [
  'jsx' => $jsx,
  'jsxs' => $jsx,
];
