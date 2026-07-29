<p align="center">
    <a href="https://phplrt.org/">
        <img src="https://avatars.githubusercontent.com/u/49816277?s=256&v=4" width="128" alt="Phplrt" />
    </a>
</p>
<p align="center">
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/require/php?style=for-the-badge" alt="PHP 8.4+"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/version?style=for-the-badge" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/v/unstable?style=for-the-badge" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/downloads?style=for-the-badge" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/phplrt/phplrt/master/LICENSE.md"><img src="https://poser.pugx.org/phplrt/phplrt/license?style=for-the-badge" alt="License MIT"></a>
</p>
<p align="center">
    <a href="https://github.com/phplrt/phplrt/actions"><img src="https://github.com/phplrt/phplrt/workflows/build/badge.svg?branch=4.x&event=push"></a>
</p>

## Introduction

The phplrt is a set of tools for programming languages recognition. The library
provides lexer, parser, grammar compiler, library for working with errors,
text analysis and so on.

## Installation

Phplrt is available as composer repository and can be
installed using the following command in a root of your project:

```bash
composer require phplrt/phplrt
```

The grammar compiler only runs while you develop, so a typical project splits
the dependency in two:

```bash
composer require phplrt/runtime          # lexer, parser and sources
composer require phplrt/compiler --dev   # reads grammars and generates code
```

More detailed installation instructions [are here](https://phplrt.org/docs/installation).

## Documentation

- https://phplrt.org/

## Quick Start

First, we will describe the language in a grammar file. A grammar says which
words the text is made of, how they may be arranged, and what to build out of
them.

> You can read more about the grammar syntax [here](https://phplrt.org/docs/compiler/grammar).

```pp3
// grammar.pp3

%skip  T_WHITESPACE  \s++

%token T_NUMBER      \d++(?:\.\d++)?
%token T_PLUS        \+
%token T_MINUS       \-

// Recognition starts from this rule
%pragma root Expression

Expression -> {
    // A single operand: nothing to add up
    if (!\is_array($children)) {
        return $children;
    }

    $result = \array_shift($children);

    while ($children !== []) {
        $operator = \array_shift($children);
        $operand = \array_shift($children);

        $result = $operator->value === '+'
            ? $result + $operand
            : $result - $operand;
    }

    return $result;
}
  : Number() ((<T_PLUS> | <T_MINUS>) Number())*
  ;

Number -> { return (float) $children->value; }
  : <T_NUMBER>
  ;
```

### Execution

To quickly check what has been written, load the grammar and ask for a parser.
The `->` blocks above run as the rules match, so what comes back is a number
rather than a syntax tree.

```php
<?php

use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

echo $parser->parse(new Source('2 + 2'));        // 4
echo $parser->parse(new Source('10 - 4 + 1.5')); // 7.5
```

There is also `analyze()`, which reports what it made of a source instead of
throwing — for validating without building, or for reading a source the
grammar is not meant to describe in full.

### Errors

Anything that cannot be recognized points at the exact spot in the source:

```php
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\VirtualFile;

try {
    // VirtualFile is a string that also has a name, so errors can name it
    $parser->parse(new VirtualFile('expr.txt', "1 + 2\n3 + + 4\n"));
} catch (UnexpectedTokenException $e) {
    echo $e;
}
```

```
error[UnexpectedTokenException]: Syntax error, unexpected "3" (T_NUMBER)
 --> expr.txt:2:1
  |
1 | 1 + 2
2 | 3 + + 4
  | ^
3 |
```

Rendering the snippet is the job of `phplrt/exception`. It comes with
`phplrt/phplrt`; alongside the separate runtime packages it is a suggestion
rather than a requirement, so add it if you install them by hand.

### Compilation

Reading a grammar file costs time, and the grammar does not change between
requests. Once it is ready and tested, compile it into a PHP file and commit
that file — after which the `phplrt/compiler` dependency is no longer needed
(see https://phplrt.org/docs/installation#which-packages-do-i-actually-ship).

```bash
vendor/bin/phplrt compile grammar.pp3 src/CalculatorParser.php \
    --namespace='App\Calculator' \
    --class=CalculatorParser
```

The same thing from PHP, if you would rather do it from a build script:

```php
new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->generate()
        ->withNamespaceName('App\Calculator')
        ->withClassName('CalculatorParser')
        ->save(__DIR__ . '/src/CalculatorParser.php');
```

What you get is an ordinary class: the whole lexer is one regular expression,
each rule is an array entry, and every reducer is a real method you can step
through in a debugger.

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

    private static function reduceNumber(\Phplrt\Parser\Context $ctx, mixed $children): mixed
    {
        return (float) $children->value;
    }
}
```

Use it like any other class — no compiler, no grammar file, no build step at
runtime:

```php
$parser = new App\Calculator\CalculatorParser();

echo $parser->parse(new Source('2 + 2')); // 4
```

## Packages

Every component is published on its own, so you can install only what you use.

| Package                 | What it does                                              |
|-------------------------|-----------------------------------------------------------|
| `phplrt/source`         | Reads source code from files, strings and streams         |
| `phplrt/lexer`          | Splits source code into tokens                            |
| `phplrt/parser`         | Recognizes tokens against a grammar and builds a result   |
| `phplrt/exception`      | Renders errors with a snippet of the code around them     |
| `phplrt/lexer-builder`  | Describes a lexer in PHP and compiles it                  |
| `phplrt/parser-builder` | Describes a grammar in PHP and compiles it                |
| `phplrt/compiler`       | Reads `*.pp2` or `*.pp3` grammars and generates PHP code  |

## License

Phplrt is open-sourced software licensed under the [MIT license](LICENSE.md).
