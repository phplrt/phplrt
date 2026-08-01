# Quick Start

Let's build a working calculator from scratch: `2 + 2 * 2` in, `6` out. It
takes about thirty lines of grammar, and along the way you will meet every
part of phplrt you are likely to need.

```bash
composer require phplrt/runtime
composer require phplrt/compiler --dev
```

## Step 1: Describe The Words

Before anything can be parsed, the text has to be cut into pieces. Create
`grammar.pp3` and start with the tokens:

```pp3
%skip  T_WHITESPACE  \s++

%token T_NUMBER      \d++(?:\.\d++)?
%token T_PLUS        \+
%token T_MINUS       \-
%token T_MUL         \*
%token T_DIV         /
%token T_PARENTHESIS_OPEN   \(
%token T_PARENTHESIS_CLOSE  \)
```

Each line is a name and a regular expression. `%skip` is the same as `%token`,
except those tokens never reach the parser - which is exactly what you want
for whitespace and comments.

The order matters: the lexer tries the patterns from top to bottom and takes
the first one that matches.

## Step 2: Describe The Sentences

Now the rules. A rule has a name, a `:`, a body, and an optional `;`:

```pp3
Number : <T_NUMBER> ;
```

`<T_NUMBER>` means "read a `T_NUMBER` token and keep it". There is also
`::T_NUMBER::`, which reads the token and throws it away - use it for
punctuation you do not care about, like commas and brackets.

Rules can refer to each other with `RuleName()`:

```pp3
Primary
  : ::T_PARENTHESIS_OPEN::
      Expression()
    ::T_PARENTHESIS_CLOSE::
  | Number()
  ;
```

`|` means "or". The alternatives are tried in order, and the first one that
matches wins - so put the more specific ones first.

To get operator precedence right, use the classic trick: multiplication binds
tighter than addition, so addition is written *in terms of* multiplication.

```pp3
Expression : Term()    ((<T_PLUS> | <T_MINUS>) Term())*    ;
Term       : Primary() ((<T_MUL>  | <T_DIV>)   Primary())* ;
```

`*` means "zero or more times". There is also `+` (one or more), `?`
(optional) and `{2,5}` (between two and five times).

## Step 3: Say What To Build

At this point the grammar is complete - it can already tell `2 + 2` from
`2 +`. But parsing alone just gives you a pile of tokens. To get a *value*,
attach a **reducer**: a block of PHP that runs when the rule matches.

```pp3
Number -> { return (float) $children->value; }
  : <T_NUMBER>
  ;
```

Inside the block, `$children` holds whatever the rule matched. For `Number`
that is a single token, so `$children->value` is the text `"42"` and the
reducer turns it into `42.0`.

For `Expression`, `$children` is a list: `[2.0, Token('+'), 3.0]`. Fold it
left to right:

```pp3
Expression -> {
    // A single operand: nothing to add up
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

## The Whole Grammar

```pp3
%skip  T_WHITESPACE  \s++

%token T_NUMBER      \d++(?:\.\d++)?
%token T_PLUS        \+
%token T_MINUS       \-
%token T_MUL         \*
%token T_DIV         /
%token T_PARENTHESIS_OPEN   \(
%token T_PARENTHESIS_CLOSE  \)

// Where the parsing starts
%pragma root Expression

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

Term -> {
    if (!\is_array($children)) {
        return $children;
    }

    $result = \array_shift($children);

    while ($children !== []) {
        $operator = \array_shift($children);
        $right = \array_shift($children);

        $result = $operator->value === '*' 
            ? $result * $right 
            : $result / $right;
    }

    return $result;
}
  : Primary() ((<T_MUL> | <T_DIV>) Primary())*
  ;

Primary
  : ::T_PARENTHESIS_OPEN::
      Expression()
    ::T_PARENTHESIS_CLOSE::
  | Number()
  ;

Number -> { return (float) $children->value; }
  : <T_NUMBER>
  ;
```

## Step 4: Run It

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

echo $parser->parse(new Source('2 + 2'));       // 4
echo $parser->parse(new Source('2 + 2 * 2'));   // 6
echo $parser->parse(new Source('(2 + 2) * 2')); // 8
echo $parser->parse(new Source('10 / 4'));      // 2.5
```

Notice the two kinds of "source": `File` reads from disk, `Source` wraps a
string you already have. The grammar is a source, and so is the text being
parsed - they are the same kind of object.

## Step 5: Errors

Feed it something broken, and you get an exception that knows where it
happened:

```php
use Phplrt\Source\VirtualFile;

// VirtualFile is a string that also has a name, so errors can point at it
$parser->parse(new VirtualFile('expr.txt', "1 + 2\n3 * (4 + )\n"));
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

If you only want to know whether the input is valid, without building
anything, use `analyze()`:

```php
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;

$parser->analyze(new Source('2 + 2'), Mode::SyntaxCheck) instanceof SuccessfulResult; // true
$parser->analyze(new Source('2 +'), Mode::SyntaxCheck) instanceof SuccessfulResult;   // false
```

It also tells you *how much* of the input is valid and what stands in the way,
which is what you want for an editor or a prompt - see
[Analysing A Source](/docs/parser#analysing-a-source).

## Step 6: Compile It Once

Reading `grammar.pp3` on every request is wasteful - the grammar does not
change between requests. Generate a PHP file instead and commit it:

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->generate()
        ->withNamespaceName('App\Calculator')
        ->withClassName('CalculatorParser')
        ->save(__DIR__ . '/CalculatorParser.php');
```

You get an ordinary class:

```php
namespace App\Calculator;

readonly class CalculatorParser extends \Phplrt\Parser\Parser
{
    public const int T_WHITESPACE = 0;
    public const int T_NUMBER = 1;
    // ...

    public function __construct()
    {
        parent::__construct(/* the whole grammar, inlined */);
    }
}
```

Which you use like any other class - no compiler, no grammar file:

```php
$parser = new App\Calculator\CalculatorParser();

echo $parser->parse(new Source('2 + 2 * 2')); // 6
```

Add the generation call to your build script or a console command, and you
are done.

## Step 7: Give The Parser Something To Work With

So far the calculator only knows what is written in the expression. Real
languages need more: a variable scope, a function registry, a logger, a cache.

The generated parser is an ordinary class, so **extend it** - and the grammar
can reach whatever you add, through `$this`.

Add a rule for variables:

```pp3
%token T_NAME  [a-z_][a-z0-9_]*+

Operand
  : Number()
  | Variable()
  ;

// $this is the parser itself, so the value comes from the subclass
Variable -> { return $this->resolve($children->value, $children->offset, $source); }
  : <T_NAME>
  ;
```

Regenerate, and notice what the compiler did with it:

```php
private static function reduceNumber(\Phplrt\Parser\Context $ctx, mixed $children): mixed
{
    return (int) $children->value;
}

private function reduceVariable(\Phplrt\Parser\Context $ctx, mixed $children): mixed
{
    $source = $ctx->source;

    return $this->resolve($children->value, $children->offset, $source);
}
```

A reducer that mentions `$this` becomes a **non-static** method, and the
grammar table refers to it as `$this->reduceVariable(...)` instead of
`self::reduceNumber(...)`. The compiler works this out per reducer, so you do
not configure anything.

Now subclass and fill in `resolve()`:

```php
use Phplrt\Contracts\Source\ReadableInterface;

readonly class Calculator extends App\Calculator\CalculatorParser
{
    /**
     * @param array<non-empty-string, int> $variables
     */
    public function __construct(
        private array $variables = [],
    ) {
        parent::__construct();
    }

    protected function resolve(string $name, int $offset, ReadableInterface $source): int
    {
        return $this->variables[$name]
            ?? throw new UnknownVariableException(
                \sprintf('Unknown variable "%s"', $name),
                $source,
                $offset,
            );
    }
}
```

```php
$parser = new Calculator(['x' => 10, 'y' => 32]);

echo $parser->parse(new Source('x + y'));     // 42
echo $parser->parse(new Source('x + 1 + y')); // 43
```

The grammar stays a description of the *language*; anything that depends on
context lives in PHP, where it belongs. Each instance carries its own data, so
two calculators with different variables are two objects.

And because `resolve()` gets the offset and the source, an error from your
code looks exactly like an error from the parser:

```
error[UnknownVariableException]: Unknown variable "zzz"
 --> expr.txt:1:5
  |
1 | x + zzz
  |     ^
```

Three things to keep in mind:

- **The generated class is `readonly`**, so a subclass must be `readonly` too,
  and its own constructor must call `parent::__construct()`.
- **`$this` reducers only work in a generated parser.** Loading the grammar
  with `getParser()` compiles reducers into plain closures with nothing to
  bind to, and the rule throws `Error: Using $this when not in object context`
  the first time it is reduced. Keep `$this` out of grammars you intend to
  read on the fly.
- **The method you call does not exist on the generated class.** It resolves
  at runtime against the subclass, which works, but analysing the generated
  file on its own reports it:

  ```
  Call to an undefined method Calc\CompiledCalculator::resolve().
  ```

  Reducer bodies are typed `mixed` anyway, so a generated parser rarely passes
  a strict analysis on its own merits. Exclude it:

  ```json
  parameters:
      excludePaths:
          - src/Parser/*Parser.php
  ```

  The reducers themselves are still checked - as part of the grammar, in code
  review - and the subclass, where your logic actually lives, is analysed
  normally.

## Where To Go Next

- [Grammar Syntax](/docs/compiler/grammar) - the full `.pp3` reference.
- [Reducers](/docs/compiler/code) - building a real AST instead of numbers.
- [Lexer](/docs/lexer) - channels, captures and nested lexers.
- [Code Generation](/docs/compiler/generation) - namespaces, imports, and
  what the generated file looks like.
