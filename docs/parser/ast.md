# Results and Reducers

A parser does two things: it decides whether the input is valid, and it builds
something out of it. The first part is the grammar. The second part is
**reducers**.

## What You Get Without Reducers

A grammar with no reducers still returns something - the tokens it kept,
nested the way the rules were:

```pp3
Sum : <T_DIGIT> (::T_PLUS:: <T_DIGIT>)* ;
```

```php
$parser->parse(new Source('2 + 3'));
// [Token("2"), Token("3")]
```

That is occasionally enough. Usually you want numbers, or AST nodes, or a
configuration array - and for that you attach a reducer.

## Attaching A Reducer

A reducer is a block of PHP that runs when its rule matches:

```pp3
Number -> { return (int) $children->value; }
  : <T_DIGIT>
  ;
```

Whatever it returns becomes the value of that rule, and gets handed to the
rule above.

The same thing through [the builder](/docs/parser/builder):

```php
use Phplrt\Parser\Context;

$number->setReducer(static fn(Context $ctx, mixed $children): int
    => (int) $children->value);
```

## What `$children` Contains

This is the part worth reading twice.

**A rule that recognizes a sequence** - a concatenation or a repetition -
gets an **array**:

```pp3
Pair : <T_DIGIT> <T_DIGIT> ;      // $children = [Token, Token]
List : <T_DIGIT>+ ;               // $children = [Token, Token, ...]
```

**Any other rule** gets the single value it recognized:

```pp3
Number : <T_DIGIT> ;              // $children = Token
Choice : Number() | Name() ;      // $children = whatever matched
```

And the arrays are **flattened into the parent**. If a nested rule returns a
list, its items are spliced into the list of the rule above rather than
nested inside it:

```pp3
Root : <T_A> Pair() <T_B> ;
Pair : <T_DIGIT> <T_DIGIT> ;

// Root's $children = [Token(a), Token(1), Token(2), Token(b)]
//               not  [Token(a), [Token(1), Token(2)], Token(b)]
```

This is deliberate - it keeps the result flat and predictable - but it means
that a rule which *should* produce a group must say so by returning a value
of its own. A reducer returning an object or a scalar is never flattened:

```pp3
Pair -> { return $children; /* an array */ }  // still flattened
Pair -> { return new PairNode($children); }   // stays one value ✔
```

Because a rule can match one thing or a list depending on the input, reducers
often start with a check:

```pp3
Expression -> {
    // Just one operand: nothing to fold
    if (!\is_array($children)) {
        return $children;
    }

    // ...
}
  : Number() ((<T_PLUS> | <T_MINUS>) Number())*
  ;
```

## Building An AST

Here is the whole pattern. Define node classes:

```php
abstract class Node
{
    public function __construct(
        public readonly int $offset,
    ) {}
}

final class NumberNode extends Node
{
    public function __construct(int $offset, public readonly float $value)
    {
        parent::__construct($offset);
    }
}

final class BinaryNode extends Node
{
    public function __construct(
        int $offset,
        public readonly string $operator,
        public readonly Node $left,
        public readonly Node $right,
    ) {
        parent::__construct($offset);
    }
}
```

Then build them in the grammar:

```pp3
%skip  T_WHITESPACE  \s++
%token T_NUMBER      \d++(?:\.\d++)?
%token T_PLUS        \+
%token T_MINUS       \-

%pragma root Expression

Expression -> {
    if (!\is_array($children)) {
        return $children;
    }

    $node = \array_shift($children);

    // Fold left: 1 + 2 - 3  =>  ((1 + 2) - 3)
    while ($children !== []) {
        $operator = \array_shift($children);
        $right = \array_shift($children);

        $node = new \BinaryNode($node->offset, $operator->value, $node, $right);
    }

    return $node;
}
  : Number() ((<T_PLUS> | <T_MINUS>) Number())*
  ;

Number -> { return new \NumberNode($offset, (float) $children->value); }
  : <T_NUMBER>
  ;
```

Parsing `1 + 2 - 3` gives you a tree:

```
BinaryNode(-)
├── BinaryNode(+)
│   ├── NumberNode(1)
│   └── NumberNode(2)
└── NumberNode(3)
```

Note `$offset` in the `Number` reducer - that is one of the
[variables the compiler provides](/docs/compiler/code). Keeping an offset on
every node is what lets you point at the right place in the source when
something goes wrong later, during type-checking or evaluation.

## The Context

Every reducer receives a `Context` as its first argument, describing where the
analysis is:

```php
static function (Context $ctx, mixed $children): mixed {
    $ctx->rule;    // int - the id of the rule being reduced
    $ctx->token;   // the last token this rule read, or null
    $ctx->source;  // the source being parsed
    $ctx->content; // its content, already read

    return null;
}
```

In a `.pp3` reducer you rarely touch `$ctx` directly, because the common
fields have shorter names - `$token`, `$offset`, `$source`. See
[PHP in a Grammar](/docs/compiler/code).

## Returning Nothing

A reducer that returns `null` leaves the value alone: the children are passed
up as if there were no reducer at all. That makes `null` useful for reducers
that only observe:

```pp3
Debug -> {
    \error_log('matched at ' . $offset);

    return null; // do not touch the result
}
  : <T_NAME>
  ;
```

## Reducers Run After Parsing

One thing to be aware of: reducers do not run while the input is being read.
The parser first recognizes the whole source, then walks what it recognized
and reduces it bottom-up.

This means:

- a reducer never sees a rule that was tried and rejected - no wasted work,
  no side effects from a branch that did not win;
- `analyze()` in `Mode::Fast` never runs reducers at all;
- a reducer cannot influence parsing. It cannot look ahead, change what is
  matched next, or fail the parse to force a different alternative. If a
  decision depends on the input, express it in the grammar - that is what
  [predicates](/docs/parser/rules) are for.
