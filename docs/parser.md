# Parser

> This package can be installed separately with `composer require phplrt/parser`

The parser is the second half of reading source code. The lexer produced a
flat list of tokens; the parser checks that they appear in an order the
grammar allows, and builds whatever you asked it to build.

## Two Methods

That is the whole runtime API:

```php
$parser->parse($source); // returns your result, or throws on a syntax error
$parser->check($source); // returns true or false, builds nothing
```

```php
use Phplrt\Source\Source;

$parser->parse(new Source('2 + 2'));  // 4
$parser->check(new Source('2 + 2'));  // true
$parser->check(new Source('2 +'));    // false
```

`check()` is the cheap one — it stops as soon as it knows the answer and
never runs a single reducer. Use it for validation, linting, or deciding
which of several grammars a file belongs to.

## Where Parsers Come From

You will rarely write one by hand. Normally you either
[compile a grammar file](/docs/compiler):

```php
$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();
```

or [generate the code once](/docs/compiler/generation) and use the class:

```php
$parser = new App\Calculator\CalculatorParser();
```

Both give you the same thing: an object implementing `ParserInterface`.

## What A Parser Actually Is

Underneath, a parser is a **flat list of rules** plus a couple of lookup
tables. Rules refer to each other by index — there are no objects pointing at
objects, just integers pointing into an array.

Here is the calculator written out by hand, so you can see the shape of it:

```php
use Phplrt\Parser\Context;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Parser;

// Sum : <T_DIGIT> (::T_PLUS:: <T_DIGIT>)*
$parser = new Parser(
    lexer: $lexer,
    grammar: [
        0 => new Concatenation([1, 2]),     // rule #1 then rule #2
        1 => new Lexeme(tokenId: 0),        // T_DIGIT, kept
        2 => new Repetition(ruleId: 3),     // rule #3, zero or more times
        3 => new Concatenation([4, 1]),
        4 => new Lexeme(tokenId: 1, keep: false), // T_PLUS, thrown away
    ],
    initial: 0, // start at rule #0
    reducers: [
        0 => static fn(Context $ctx, mixed $children): int => \array_sum(
            \array_map(static fn($token) => (int) $token->value, $children),
        ),
    ],
);

echo $parser->parse(new Source('1 + 2 + 3')); // 6
```

This is exactly what the compiler generates for you — it just also computes
the lookahead tables, which make it considerably faster.

The rule classes are described in [Grammar Rules](/docs/parser/rules), and
the friendlier way to produce this array is
[the parser builder](/docs/parser/builder).

## How It Reads

Phplrt recognizes a
[PEG](https://en.wikipedia.org/wiki/Parsing_expression_grammar) with a
backtracking, table-driven recursive descent, guided by FIRST sets, and it
builds the result only after the whole input has been recognized.

That is a mouthful, so taken apart:

**Table-driven.** The grammar is a flat array of rules addressed by index, not
a set of generated functions. Recognizing a rule is `match($id)`, which looks
the definition up and dispatches on its type. This is what makes a grammar
plain data: it can be optimized, dumped to a file and loaded back.

**Recursive descent with backtracking.** Rules are tried top-down. When an
alternative fails, the token stream is rewound to where the rule started and
the next alternative is tried; a failed concatenation rewinds the same way.
There is no memoization — this is not a packrat parser — so a grammar that
backtracks heavily pays for it. In practice the FIRST sets keep that from
happening.

**FIRST-set prediction.** Before descending into a rule, the parser checks
whether the current token can start it at all:

```php
if (!isset($startTokens[$rule][$token->id]) && !$matchesEmptyInput[$rule]) {
    return false;
}
```

`startTokens` is computed by the builder for every rule. It turns "try this
rule and find out" into one array lookup, which is where most of the speed
comes from — and it is why *not* passing the lookahead tables to `Parser`
leaves you with a plain PEG that still works but is slower.

**Deferred tree construction.** Recognition produces a flat list of trace
entries — a rule id on the way in, the tokens read, a negative rule id on the
way out. Nothing is built while the input is being read, and a branch that
fails simply has its entries truncated. The tree is assembled afterwards, in
one pass, by running the reducers bottom-up. `check()` skips that pass
entirely.

Two consequences are worth knowing before you write a grammar.

**Alternatives are ordered.** The first one that matches wins, and the rest
are not tried:

```pp2
Rule : "a" | "ab" ;
```

This never reads `ab`. `"a"` already matched, and the parser does not go back
to look for something longer. Put the longer alternative first:

```pp2
Rule : "ab" | "a" ;
```

The upside is that a grammar is never ambiguous: there is exactly one way to
read any input, and you can always tell which one by reading top to bottom.

**Left recursion is not allowed.** A rule cannot start with itself:

```pp2
// This never terminates, and the builder will refuse it
Expression : Expression() ::T_PLUS:: Number() ;
```

Write it as a repetition instead — this is the standard translation, and it
is what you want anyway:

```pp2
Expression : Number() (::T_PLUS:: Number())* ;
```

The builder detects left recursion while compiling and reports it, so you
will not discover this at runtime.

## Syntax Errors

`parse()` throws `UnexpectedTokenException` when the input does not match:

```php
use Phplrt\Parser\Exception\UnexpectedTokenException;

try {
    $parser->parse(new VirtualFile('expr.txt', "1 + 2\n3 * (4 + )\n"));
} catch (UnexpectedTokenException $e) {
    echo $e->getMessage(); // Syntax error, unexpected "3" (T_NUMBER)
    echo $e;               // ...plus the snippet below
}
```

```
error[UnexpectedTokenException]: Syntax error, unexpected "3" (T_NUMBER)
 --> expr.txt:2:1
  |
1 | 1 + 2
2 | 3 * (4 + )
  | ^
3 |
```

The exception carries the token it choked on, so you can build your own
message:

```php
$e->token->name;   // T_NUMBER
$e->token->offset; // 6
$e->source;        // the source it was reading
```

Because the parser backtracks, "the token it choked on" needs a definition.
The reported position is the **furthest** one any rule reached before failing,
not the position where the last attempt gave up — otherwise every error would
point at the start of the outermost rule. This usually lands where you expect,
but it can surprise you: an alternative that got further into the input before
failing wins the report, even if a different alternative was the intended one.

See [Error Reporting](/docs/errors) for the full picture.

## Input Must Be Consumed Entirely

A parse succeeds only if the grammar reads the whole source. Trailing junk is
an error, not a stopping point:

```php
$parser->parse(new Source('2 2')); // Syntax error, unexpected "2" (T_NUMBER)
```

If you want to read a prefix and stop, say so in the grammar.

## Next

- [Grammar Rules](/docs/parser/rules) — the five rule types and what each
  one does.
- [Building a Grammar](/docs/parser/builder) — describing rules in PHP.
- [Results and Reducers](/docs/parser/ast) — turning a parse into an AST.
