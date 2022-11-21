import React from 'react';

let l;
const c: number = 1;
{
  const a = [1, 2, 3];
}

undefined ? c : 2;
true ? c : 2;
null ? c : 2;

const { aa, bb: bb1 = '2', cc = 1, dd: { aa: aa1 } } = {
  aa: 'aa',
  cc: c,
  dd: {
    aa: 'aa',
  },
}

import chalk from 'chalk';

export const u = undefined;
const n: null = null;

typeof 1;
typeof true;
typeof '';
typeof 1.1;
typeof {};
typeof [];

function fun(n: string = 'fin') {
  return 'This was ' + n + '!';
}

const bb = aaa(2);

function aaa(b: number) {
  return b + 1;
}

type Props = {
  source?: string,
  title?: string,
  description?: string,
  children?: React.ReactNode,
}

export function Header({
  source: src = '',
  title = '',
  description,
  children,
  ...rest
}: Props = {
    source: 'favicon.ico',
    title: 'Hello world?',
    description: 'Just the lorem ipsum...',
    children: null,
  }) {
  return (
    <div {...rest}>
      <img src={src} />
      <h1>{title} </h1>
      <p> {description} </p>
      {'text'}
      text
      {children ? children : 'empty'}
    </div>
  )
}

const defaultProps = {
  source: 'favicon.ico',
  title: 'Hello world?',
  description: 'Just the lorem ipsum...',
  children: null,
}

const interMediateProps = {
  title: 'Hello new world!',
}

const rest = {}

export const rendered = (
  <>
    <Header />
    <Header
      {...defaultProps}
      source="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9Ii0xMS41IC0xMC4yMzE3NCAyMyAyMC40NjM0OCI+CiAgPHRpdGxlPlJlYWN0IExvZ288L3RpdGxlPgogIDxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSIyLjA1IiBmaWxsPSIjNjFkYWZiIi8+CiAgPGcgc3Ryb2tlPSIjNjFkYWZiIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiPgogICAgPGVsbGlwc2Ugcng9IjExIiByeT0iNC4yIi8+CiAgICA8ZWxsaXBzZSByeD0iMTEiIHJ5PSI0LjIiIHRyYW5zZm9ybT0icm90YXRlKDYwKSIvPgogICAgPGVsbGlwc2Ugcng9IjExIiByeT0iNC4yIiB0cmFuc2Zvcm09InJvdGF0ZSgxMjApIi8+CiAgPC9nPgo8L3N2Zz4K"
      // @ts-expect-error: 'title' is specified more than once, so this usage will be overwritten.
      title="Hello world!"
      {...interMediateProps}
      description="Lorem ipsum dolor sit amet..."
      {...rest}
    >
      Hello!
    </Header>
  </>
)

const ffn = function ffn() {
  return fun('fun');
};
const fffn = function () {
  return ffn();
};

const fffn_result = fffn();

console.log(fffn_result);

const typeof_n = typeof n;

' some '.trim();

1 == 1;
1 === 1;

const trim = (new String(' a ')).trim;
const a: string = new String(' a ').trim();

type O = Record<string | number, any>;

const o: O = {
  a,
  b: 'b',
  c,
  false: false,
  1: 1,
  true: true,
  null: null,
  f: () => {
    console.log('f');
  },
  ff: function () { },
  fff: function fff() { return 1; },
};

const o2 = new Object(o);

o.f && o.f();

o.next = 'new';

const b = o.next === 'next';

const f = (a: number) => {
  a + 'string';
  'string' + a;

  const n = null;

  return true || (false && undefined) || n || a / 2 || a * 2;

  return a ** 2;
  return a * 2;

  return a + 1;
};

f(2);

const ab: string = ' a '.trim();

const oo: Object = new Object(null);

console.log(aa);
