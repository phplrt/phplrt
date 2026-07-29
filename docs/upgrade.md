# Upgrade Guide

## Upgrading To 4.0 From 3.x

Version 4.0 is a rewrite. Almost every public API changed, so this is a
porting guide rather than a list of renames. The good news: the grammar
files mostly survive, and that is where the bulk of the work usually lives.

### PHP 8.4 Required

> Likelihood Of Impact: **High**

Phplrt 4.0 requires PHP 8.4. The API uses property hooks, asymmetric
visibility and `new` in initializers throughout - which is also why so much
of it looks different.

### Getters Became Properties

> Likelihood Of Impact: **High**

Everything that was `getX()` is now a property. This is the single most
common change you will hit.

```php
// 3.x
$token->getName();
$token->getOffset();
$token->getValue();
$token->getBytes();

$source->getContents();
$source->getPathname();

// 4.x
$token->name;
$token->offset;
$token->value;
$token->size;

$source->content;
$source->pathname;
```

### Tokens Are Identified By Number

> Likelihood Of Impact: **High**

In 3.x a token was addressed by its name. In 4.x it has an `int $id`, and the
name is optional metadata for error messages.

```php
// 3.x
if ($token->getName() === 'T_DIGIT') { /* ... */ }

// 4.x
if ($token->id === MyParser::T_DIGIT) { /* ... */ }
```

[Generated parsers](/docs/compiler/generation) expose the ids as class
constants, so you do not have to track the numbers yourself.

### Channels Replaced "Skipped Tokens"

> Likelihood Of Impact: **Medium**

The lexer no longer takes a list of names to skip. Every token now carries a
[channel](/docs/lexer/tokens), and the parser reads the default one.

```php
// 3.x
$lexer = new Lexer($tokens, ['T_WHITESPACE']);

// 4.x
$builder->addPattern('\s++', 'T_WHITESPACE')
    ->hide();
```

Unlike skipping, a hidden token is still produced - so you can look at
comments and whitespace when you need them, and custom channels let you keep
a token out of the grammar without throwing it away.

### The Lexer Is Built, Not Configured

> Likelihood Of Impact: **High**

`Phplrt\Lexer\Lexer` no longer takes a map of names and patterns. It takes a
single compiled regular expression and a set of tables - which you get from
`LexerBuilder`.

```php
// 3.x
$lexer = new Lexer([
    'T_DIGIT' => '\d+',
    'T_PLUS'  => '\+',
]);

// 4.x
use Phplrt\Lexer\Builder\LexerBuilder;

$builder = new LexerBuilder();
$builder->addPattern('\d++', 'T_DIGIT');
$builder->addValue('+', 'T_PLUS');

$lexer = $builder->build()
    ->toLexer();
```

The `append()`, `prepend()`, `prependMany()` and `skip()` methods are gone;
`addPattern()`, `addValue()` and `hide()` cover the same ground. Building is
also where patterns are validated, so a broken regex is reported with the
token that owns it instead of failing at match time.

### The Parser Takes Explicit Arguments

> Likelihood Of Impact: **High**

The `Parser::CONFIG_*` options are gone, replaced by constructor parameters:

```php
// 3.x
$parser = new Parser($lexer, $grammar, [
    Parser::CONFIG_INITIAL_RULE => 'expression',
    Parser::CONFIG_AST_BUILDER  => new MyBuilder(),
]);

// 4.x
$parser = new Parser(
    lexer: $lexer,
    grammar: $grammar,
    initial: 0,
    reducers: [0 => $callback],
);
```

Rules are keyed by **integers** rather than by name, and they refer to each
other by index. In practice you do not write this array by hand - see
[the parser builder](/docs/parser/builder).

### Checking And Trailing Tokens Became One Method

> Likelihood Of Impact: **Medium**

3.x answered "is this valid?" with `check()`, and read a source the grammar
does not describe in full through a setting, picking up where it stopped from
the parser afterwards. Both are `analyze()` now, and it returns what it found
rather than keeping it:

```php
// 3.x
$parser->check($source); // true or false

$parser = new Parser(..., [Parser::CONFIG_ALLOW_TRAILING_TOKENS => true]);
$parser->parse($source);

$context = $parser->getLastExecutionContext();
$context->buffer->current(); // where the parser stopped

// 4.x
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;

$parser->analyze($source, Mode::SyntaxCheck) instanceof SuccessfulResult; // true or false

$result = $parser->analyze($source);

$result->value;       // what the fragment reduced to
$result->token;       // where the parser stopped
$result->diagnostics; // and what stands in the way
```

Two things changed beyond the names. Nothing is kept on the parser between
calls, so a parser is safe to share and to call from several places at once.
And where the reading stopped is now told for every source, not only for the
ones that fail: a valid source is a `SuccessfulResult`, a source the grammar
reads in part is a `PartialResult`, and one it cannot read at all is a
`FailureResult`.

See [Analysing A Source](/docs/parser#analysing-a-source).

### BuilderInterface Became Per-Rule Reducers

> Likelihood Of Impact: **High**

The single AST builder with a `switch` overrule names is gone. Each rule now
carries its own reducer.

```php
// 3.x
class MyBuilder implements BuilderInterface
{
    public function build(Context $ctx, $children)
    {
        switch ($ctx->getState()) {
            case 'Number': return new NumberNode($children);
            case 'Sum':    return new SumNode($children);
        }

        return null;
    }
}

// 4.x - in the grammar file
Number -> { return new \NumberNode($offset, $children->value); } 
    : <T_DIGIT> ;
Sum    -> { return new \SumNode($children); } 
    : Number() ::T_PLUS:: Number() ;
```

```php
// 4.x - or through the builder
$number->setReducer(static fn(Context $ctx, mixed $children): NumberNode
    => new NumberNode($children)
);
```

The reducer signature is `callable(Context $ctx, mixed $children): mixed`,
same as before, but `$ctx->getState()` is now `$ctx->rule` and holds an
integer.

Returning `null` still means "leave the children alone".

### Grammar Rule Classes

> Likelihood Of Impact: **Medium**

The rules moved from `Phplrt\Parser\Grammar` (3.2) - same namespace, but they
are now `readonly` value objects that reference other rules by **integer id**,
and `Lexeme` takes a token id instead of a name.

```php
// 3.x
new Lexeme('T_DIGIT');
new Lexeme('T_WHITESPACE', false);
new Repetition($ruleId, 0, \INF);

// 4.x
new Lexeme(MyLexer::T_DIGIT);
new Lexeme(MyLexer::T_WHITESPACE, false);
new Repetition($ruleId, 0, \INF);
```

`reduce()` is gone from the rule classes: matching is done by the parser's
internal tracer, and the rules are pure data. This is what made the lookahead
tables and code generation possible.

### The Buffer Package Is Gone

> Likelihood Of Impact: **Low**

`phplrt/buffer` was merged into `phplrt/parser` as an internal detail
(`Phplrt\Parser\Internal\Buffer`). The parser no longer exposes a buffer, and
you do not choose one.

### The Position and Visitor Packages Are Gone

> Likelihood Of Impact: **Medium**

`phplrt/position` and `phplrt/visitor` have been removed.

Positions: the exception component computes lines and columns when it renders
an error, which was the main use for it. See
[Error Reporting](/docs/errors).

Visitors: 4.x does not prescribe an AST shape, so it cannot prescribe a way to
walk one. Your nodes are your own classes; walk them however suits them.

### Sources Are Constructed Directly

> Likelihood Of Impact: **Medium**

The static factory methods on `File` are gone.

```php
// 3.x
File::fromPathname('/app/x.txt');
File::fromSources('2 + 2');

// 4.x
new File('/app/x.txt');
new Source('2 + 2');
new VirtualFile('x.txt', '2 + 2'); // a string with a name
```

`SourceFactory` is there if you need the "figure out what this is" behaviour.

### Code Generation Is Back

> Likelihood Of Impact: **Low**

3.0 removed generation of a full PHP class in favour of a config array. 4.0
brings the class back, and it now includes the lexer, the token constants and
the reducers as real methods:

```php
new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->generate()
        ->withNamespaceName('App\Parser')
        ->withClassName('LanguageParser')
        ->save(__DIR__ . '/LanguageParser.php');
```

See [Code Generation](/docs/compiler/generation).

### The `.pp` Format Is No Longer Read

> Likelihood Of Impact: **Medium**

Grammars written in the original Hoa-style `.pp` format are not supported.
A `.pp` file is still recognized by its extension, so you get a clear error
rather than a confusing parse failure.

Rewrite the grammar in one of the formats that are read - see
[Grammar Syntax](/docs/compiler/grammar).

### Grammar Files: What To Check

> Likelihood Of Impact: **Medium**

A grammar file keeps its extension and keeps being read the way it was, so most
of them compile unchanged. What no longer works:

**The old pragmas are no longer supported.** Unification and the error levels
are gone; the corresponding behaviour is either the default now or is
configured in PHP. Which settings a grammar may carry is listed under
[Settings](/docs/compiler/grammar).

**`$file` and `$state` are no longer declared in a reducer.** Use `$source` and
`$rule`, which is an `int`. See [PHP in a Grammar](/docs/compiler/code).

**Left recursion is now rejected at build time.** It never worked at runtime
either, but 3.x would let you compile it. Rewrite as a repetition:

```pp2
// ✘ rejected
Expression : Expression() ::T_PLUS:: Number() ;

// ✔
Expression : Number() (::T_PLUS:: Number())* ;
```

**Reducers returning `null`** mean "no result" and pass the children through.
If a rule of yours legitimately produces `null`, wrap it.

**Reducers returning arrays** are flattened into the rule above. If you
relied on nesting, return an object instead. See
[Results and Reducers](/docs/parser/ast).

### Package Names

> Likelihood Of Impact: **Low**

| 3.x                        | 4.x                                         |
|----------------------------|---------------------------------------------|
| `phplrt/lexer`             | `phplrt/lexer` (+ `phplrt/lexer-builder`)   |
| `phplrt/parser`            | `phplrt/parser` (+ `phplrt/parser-builder`) |
| `phplrt/compiler`          | `phplrt/compiler`                           |
| `phplrt/source`            | `phplrt/source`                             |
| `phplrt/exception`         | `phplrt/exception`                          |
| `phplrt/buffer`            | removed - merged into `phplrt/parser`       |
| `phplrt/position`          | removed                                     |
| `phplrt/visitor`           | removed                                     |
| `phplrt/ast-contracts`     | removed - 4.x does not define a node shape  |

## Upgrading To 3.2 From 3.1

### Grammar Package Removed

> Likelihood Of Impact: **Medium**

The `phplrt/grammar` and `phplrt/grammar-contracts` has been removed. All
classes and interfaces associated with this package have been moved inside
the `phplrt/parser` package.

- All classes `Phplrt\Grammar\*` has been renamed to `Phplrt\Parser\Grammar\*`.
- All interfaces `Phplrt\Contracts\Grammar\*` has been renamed to
  `Phplrt\Parser\Grammar\*`.

### Buffer Package Has Been Moved

> Likelihood Of Impact: **Medium**

- Interface `Phplrt\Contracts\Lexer\BufferInterface` has been moved
  into `Phplrt\Buffer\BufferInterface`.
- All classes `Phplrt\Lexer\Buffer\*` has been moved into `Phplrt\Buffer\*`.
