# Building a Grammar

> This package can be installed separately with
> `composer require phplrt/parser-builder`

Writing the [rule array](/docs/parser/rules) by hand works, but you have to
keep track of indices yourself, and one inserted rule renumbers everything.
The builder does that for you: you describe rules as objects, and it turns
them into the flat array - validating and optimizing along the way.

This is also what the [grammar compiler](/docs/compiler) uses internally. A
`.pp3` file is easier to read, so reach for the builder when the grammar is
not known ahead of time: assembled from plugins, from a config file, from a
database.

## A First Grammar

A grammar needs a lexer to refer to, so both builders work together:

```php
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\Source;

// --------------------------------------------
//  Lexer Builder
// --------------------------------------------
$lexer = new LexerBuilder();
$digit = $lexer->addPattern('\d++', 'T_DIGIT');
$plus  = $lexer->addValue('+', 'T_PLUS');
$lexer->addPattern('\s++')
    ->hide();


// --------------------------------------------
//  Parser Builder
// --------------------------------------------
$grammar = new ParserBuilder();

// Sum : <T_DIGIT> (::T_PLUS:: <T_DIGIT>)*
$sum = $grammar->addConcatenation([
    $number = $grammar->addTokenReference($digit),
    $grammar->addRepetition(
        $grammar->addConcatenation([
            $grammar->addTokenReference($plus)
                ->skip(),
            $number,
        ]),
    ),
]);

// Parsing should start with "$sum"
$grammar->setInitialRule($sum);

// Building process
$compiledLexer = $lexer->build();
$compiledParser = $grammar->build($compiledLexer);

$parser = $compiledParser->toParser(
    $compiledLexer->toLexer(),
);

$parser->parse(new Source('1 + 2 + 3'));
```

Every `add*()` method returns the rule it created, so you can nest calls or
pull them out into variables - whichever reads better.

## The Rules

```php
// A token, by definition, by name or by id
$grammar->addTokenReference($digit);
$grammar->addTokenReference('T_DIGIT');
$grammar->addTokenReference(0);

// ...and one that is read but thrown away
$grammar->addTokenReference('T_COMMA')
    ->skip();

// a b c
$grammar->addConcatenation([$a, $b, $c]);

// a | b | c
$grammar->addAlternation([$a, $b, $c]);

// a?
$grammar->addOptional($a);

// a*        a+                 a{2,5}
$grammar->addRepetition($a);
$grammar->addRepetition($a, min: 1);
$grammar->addRepetition($a, min: 2, max: 5);

// &a and !a - look ahead without reading
$grammar->addPredicate($a);
$grammar->addPredicate($a, isExpected: false);
```

Referring to a token by **definition** is the safest: rename it later and the
grammar still works. By **name** is useful when the token is declared
elsewhere - the reference is resolved when the grammar is built, so the order
of declaration does not matter.

## Naming Rules and Referring To Them

Every `add*()` method takes an optional name, and a named rule can be pointed
at before it exists:

```php
$grammar->addConcatenation([
    $grammar->addTokenReference('T_IF'),
    $grammar->addRuleReference('Expression'), // not defined yet - fine
], name: 'IfStatement');

$grammar->addAlternation([...], name: 'Expression');
```

A `RuleReference` is a placeholder: it is replaced by the rule it points at
while the grammar is being built, and never reaches the compiled parser. If
nothing defines that name, you get a clear error:

```
Rule Expression = Missing refers to the rule named "Missing",
which has not been defined
```

Names are for building only. Once the parser is compiled, rules are numbers -
except for the ones with a reducer, whose names survive so the generated
methods can be called after them.

## Reducers

A reducer turns a matched rule into a value. Attach one with `setReducer()`:

```php
use Phplrt\Parser\Context;

$number = $grammar->addTokenReference('T_DIGIT', 'Number')
    ->setReducer(static fn(Context $ctx, mixed $children): int
        => (int) $children->value,
    );
```

Any callable works. But note: a closure cannot be written into a generated
file. If you plan to [generate code](/docs/compiler/generation), define the
reducer as PHP source instead:

```php
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;

$number->setReducer(new PhpCodeReducer(
    'return (int) $children->value;'
));
```

`PhpCodeReducer` works both ways - it runs in memory *and* it can be dumped
into the generated parser. `CallableReducer` (which you get implicitly when
passing a closure) only runs in memory.

## Where To Start

By default the grammar starts at the first rule added. Say otherwise with
`setInitialRule()`:

```php
$grammar->setInitialRule($expression);
```

In a `.pp3` file this is `%pragma root Expression`.

## What The Builder Checks

`build()` does more than assemble an array. It runs a pipeline of passes,
and several of them exist purely to tell you that a grammar is wrong before
you ship it:

- **references are resolved** - every `RuleReference` is replaced by the rule
  it names;
- **unreachable rules are dropped** - a rule nothing refers to is not
  compiled, and does not have to be correct;
- **token references are checked** - a rule pointing at a token the lexer does
  not have is an error;
- **left recursion is rejected**:

```pp3
Rule Expression = (...) | <name is "T_NUMBER"> is left recursive:
Expression -> (...) -> Expression
```

Then it optimizes. Redundant wrappers are removed, nested concatenations are
joined, duplicate rules are merged, repeated alternatives are dropped. A
real-world grammar typically loses a few percent of its rules this way, and
parses a little faster for it.

Finally it computes the **lookahead tables**: for every rule, the set of
tokens it can possibly start with. At parse time this turns "try this rule and
see" into a single array lookup, which is most of the reason the parser is
quick.

## Adding Your Own Passes

The pipeline is open. A pass gets the whole grammar and may rewrite it:

```php
use Phplrt\Parser\Builder\ParserBuilder;

$grammar->addCompilerPass(new MyValidationPass(), ParserBuilder::PASS_PRIORITY_CHECK);
```

The priorities, in the order they run:

| Priority                             | What belongs there                         |
|--------------------------------------|--------------------------------------------|
| `PASS_PRIORITY_NORMALIZE`            | Bring the grammar to a canonical shape     |
| `PASS_PRIORITY_CHECK`                | Reject a grammar that cannot be compiled   |
| `PASS_PRIORITY_OPTIMIZE`             | Rewrite it, keeping the meaning            |
| `PASS_PRIORITY_CHECK_AFTER_OPTIMIZE` | Catch an optimization that broke it        |

A built-in pass can be dropped by name, which is how you opt out of an
optimization that does not suit your grammar:

```php
use Phplrt\Parser\Builder\Compiler\NestedConcatenationParserCompilerPass;

$grammar->removeCompilerPass(NestedConcatenationParserCompilerPass::class);
```

There are analysis passes too - they do not change the grammar, they describe
it (this is where the lookahead tables come from):

```php
$grammar->addAnalysisPass(new MyMetadataPass());
```

A `.pp3` grammar can do all of this itself, without any PHP around it:

```pp3
%pragma parser.check    \App\Grammar\MyValidationPass
%pragma parser.disable  \Phplrt\Parser\Builder\Compiler\NestedConcatenationParserCompilerPass
```

See [Settings](/docs/compiler/grammar).

## The Result

`build()` returns a `ParserBuilderResult` - everything a parser needs,
still as data:

```php
$result = $grammar->build($compiledLexer);

$result->grammar;    // list<RuleInterface>
$result->initial;    // int
$result->reducers;   // array<int, ReducerInterface>
$result->constants;  // ['Sum' => 0, 'Number' => 1]
$result->startTokens; // the lookahead table

$parser = $result->toParser($compiledLexer->toLexer());
```

Keeping the result around is what makes code generation possible: the same
data that runs in memory can be written out as PHP.
