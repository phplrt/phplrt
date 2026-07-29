# Installation

Phplrt is installed with [composer](https://getcomposer.org/doc/00-intro.md).

## Requirements

  * PHP 8.4 or above
  * [PCRE Extension](https://php.net/manual/en/book.pcre.php)
  * [Mbstring Extension](https://www.php.net/manual/en/mbstring.installation.php) (recommended)

## Everything At Once

The simplest way to start is to install the whole project:

```bash
composer require phplrt/phplrt
```

This gives you the lexer, the parser, the builders and the grammar compiler.

## Only What You Need

Every component is also published on its own, so you can install just the
parts you actually use. This matters in production: the compiler reads
grammar files and generates code, which is something you normally do while
developing, not while serving requests.

```bash
# Runtime: reads source code and parses it
composer require phplrt/runtime

# Development: turns a grammar file into a lexer and a parser
composer require phplrt/compiler --dev
```

Here is the full list:

| Package                  | What it does                                                          |
|--------------------------|-----------------------------------------------------------------------|
| `phplrt/source`          | Reads source code from files, strings and streams                     |
| `phplrt/lexer`           | Splits source code into tokens                                        |
| `phplrt/parser`          | Recognizes tokens against a grammar and builds a result               |
| `phplrt/exception`       | Renders errors with a snippet of the code around them                 |
| `phplrt/lexer-builder`   | Describes a lexer in PHP and compiles it                              |
| `phplrt/parser-builder`  | Describes a grammar in PHP and compiles it                            |
| `phplrt/compiler`        | Reads `*.pp2` or `*.pp3` grammar files and generates PHP code |

And the contracts, if you only want to type-hint against interfaces:

```bash
composer require phplrt/lexer-contracts phplrt/parser-contracts phplrt/source-contracts
```

## Autoloading

As with any composer package, include the autoloader:

```php
require __DIR__ . '/vendor/autoload.php';
```

## Which Packages Do I Actually Ship?

It depends on how you use phplrt.

**If you generate a parser** (recommended), the generated file is plain PHP
that only refers to the runtime. The compiler is a development dependency:

```json
{
    "require": {
        "phplrt/runtime": "^4.0"
    },
    "require-dev": {
        "phplrt/compiler": "^4.0"
    }
}
```

Or name the pieces yourself, if you would rather not pull in a metapackage:

```json
{
    "require": {
        "phplrt/lexer": "^4.0",
        "phplrt/parser": "^4.0",
        "phplrt/source": "^4.0"
    }
}
```

**If you read the grammar file at runtime**, you need `phplrt/compiler` in
production too. That is fine for a script or a one-off tool, but it is slower:
the grammar has to be read and compiled on every run.
