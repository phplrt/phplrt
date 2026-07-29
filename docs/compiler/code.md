# PHP in a Grammar

A grammar on its own only answers "is this valid?". To get a *value* out of a
parse — a number, an AST node, a configuration array — you attach PHP to a
rule. That piece of PHP is called a **reducer**, and it runs when the rule
matches.

## A Block of Code

Put it between `->` and the rule body:

```pp2
Number -> { return (int) $children->value; }
  : <T_DIGIT>
  ;
```

Whatever it returns becomes the value of the rule. The code is ordinary PHP —
loops, conditionals, whatever you need:

```pp2
Expression -> {
    if (!\is_array($children)) {
        return $children;
    }

    $result = \array_shift($children);

    while ($children !== []) {
        $operator = \array_shift($children);
        $right = \array_shift($children);

        $result = $operator->value === '+' 
            ? $result + $right 
            : $result - $right;
    }

    return $result;
}
  : Term() ((<T_PLUS> | <T_MINUS>) Term())*
  ;
```

Braces inside strings are safe — the block is read by a real PHP lexer, so
`"{"` is a string, not the end of the block.

## Building A Node

A block of code is the only form a reducer takes, so a rule that maps onto a
node class builds it there:

```pp2
Number -> { return new \App\Ast\NumberNode($offset, (int) $children->value); }
  : <T_DIGIT>
  ;
```

```php
namespace App\Ast;

final class NumberNode
{
    public function __construct(
        public readonly int $offset,
        public readonly int $value,
    ) {}
}
```

Passing the node exactly what it needs is a little more typing than handing
the whole context over, and it keeps the node a plain value object that knows
nothing about the parser.

## The Variables

Inside a code block, these are available:

| Variable    | What it is                                             |
|-------------|--------------------------------------------------------|
| `$children` | What the rule matched. **This is the important one.**  |
| `$ctx`      | The full `Context` object                              |
| `$token`    | The last token the rule read, or `null`                |
| `$offset`   | Where that token starts, in bytes                      |
| `$source`   | The source being parsed                                |
| `$content`  | Its contents, already read                             |
| `$rule`     | The id of the rule being reduced                       |

All except `$children` and `$ctx` are shorthands the compiler expands for
you — `$offset` becomes `$ctx->token->offset`, and so on. They are only
declared if you use them, so there is no cost to the ones you do not.

```pp2
Number -> { return new \NumberNode($offset, (float) $children->value); }
  : <T_NUMBER>
  ;
```

Keeping an offset on every node is a habit worth forming. It is what lets a
later stage — a type checker, an evaluator, a linter — point at the right
place in the source when it finds a problem.

## What `$children` Holds

**A sequence** (a concatenation or a repetition) gives you an **array**:

```pp2
Pair : <T_DIGIT> <T_DIGIT> ;   // $children = [Token, Token]
List : <T_DIGIT>+ ;            // $children = [Token, Token, ...]
```

**Anything else** gives you a single value:

```pp2
Number : <T_DIGIT> ;           // $children = Token
Choice : Number() | Name() ;   // $children = whatever matched
```

A rule that can match one thing *or* several will hand you one thing or
several, which is why reducers so often start with:

```pp2
Rule -> {
    if (!\is_array($children)) {
        return $children;
    }

    // ...
}
```

The [Results and Reducers](/docs/parser/ast) page goes into how nested values
are combined.

## Returning Nothing

Return `null` and the children pass through untouched, as if the reducer were
not there:

```pp2
Debug -> {
    \error_log('reached rule ' . $rule . ' at ' . $offset);

    return null; // leave the result alone
}
  : <T_NAME>
  ;
```

An empty block (`-> {}`) is the same as writing no reducer at all.

## In Generated Code

When you [generate a parser](/docs/compiler/generation), reducers become real
methods, named after the rule they belong to:

```php
private static function reduceNumber(\Phplrt\Parser\Context $ctx, mixed $children): mixed
{
    return (float) $children->value;
}
```

Two practical consequences.

**Your code appears verbatim in the generated file.** It is debuggable and
steppable, and a syntax error in a reducer is a syntax error in that file —
so run the generator as part of your build, not at deploy time.

**A grammar file has no `use` statements**, so how a short class name resolves
depends on where the reducer ends up — the global namespace when the grammar
is read on the fly, the generated file's namespace when it is generated. The
safe answer is to write class names fully qualified:

```pp2
// ✔ works either way
Number -> { return new \App\Ast\NumberNode($offset, $children->value); }
```

If the fully qualified names make a big grammar unreadable, you can declare
the imports on the generated file instead:

```php
new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->generate()
        ->withNamespace('App\Parser')
        ->withClassImport('App\Ast\NumberNode')
        ->save(__DIR__ . '/Parser.php');
```

```pp2
Number -> { return new NumberNode($offset, $children->value); }
```

The trade-off: that grammar now only works when generated. Pick one approach
per project rather than mixing them.
