<p align="center">
    <a href="https://phplrt.org/">
        <img src="https://avatars.githubusercontent.com/u/49816277?s=256&v=4" width="128" alt="Phplrt" />
    </a>
</p>
<p align="center">
    <a href="https://github.com/phplrt/phplrt/actions"><img src="https://github.com/phplrt/phplrt/workflows/build/badge.svg"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://img.shields.io/badge/PHP-7.4+-ff0140.svg" alt="PHP 7.1+"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/version" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/v/unstable" alt="Latest Unstable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/downloads" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/phplrt/phplrt/master/LICENSE.md"><img src="https://poser.pugx.org/phplrt/phplrt/license" alt="License MIT"></a>
</p>

<p align="center">
    <a href="https://opencollective.com/phplrt/donate?" target="_blank">
      <img src="https://opencollective.com/phplrt/donate/button@2x.png" width="200" />
    </a>
</p>

## Thanks To

<table>
    <tr>
        <td width="225" align="center">
            <a href="https://www.antlr.org/" target="_blank" rel="nofollow">
                <img src="https://phplrt.org/img/thanks/antlr-logo.png" alt="Antlr" height="48" />
            </a>
        </td>
        <td width="225" align="center">
            <a href="https://hoa-project.net/" target="_blank" rel="nofollow">
                <img src="https://phplrt.org/img/thanks/hoa.svg" alt="Hoa Project" height="48" />
            </a>
        </td>
        <td width="225" align="center">
            <a href="https://github.com/nikic/PHP-Parser" target="_blank" rel="nofollow">
                <img src="https://phplrt.org/img/thanks/php-parser.png" alt="nikic/PHP-Parser" height="48" />
            </a>
        </td>
        <td width="225" align="center">
            <a href="https://www.jetbrains.com/" target="_blank" rel="nofollow">
                <img src="https://phplrt.org/img/thanks/jetbrains.svg" alt="JetBrains" height="64" />
            </a>
        </td>
    </tr>
</table>


## Introduction

The phplrt is a set of tools for programming languages recognition. The library
provides lexer, parser, grammar compiler, library for working with errors,
text analysis and so on.

## Installation

Phplrt is available as composer repository and can be
installed using the following command in a root of your project:

```bash
$ composer require phplrt/phplrt
```

More detailed installation instructions [are here](https://phplrt.org/docs/installation).

## Documentation

- https://phplrt.org/

## Quick Start

First, we will create the grammar for our parser.

> You can read more about the grammar syntax [here](https://phplrt.org/docs/compiler/grammar).

```php
<?php

use Phplrt\Compiler\Compiler;

$compiler = new Compiler();
$compiler->load(<<<EBNF

    %token T_DIGIT          \d
    %token T_PLUS           \+
    %token T_MINUS          \-
    %token T_POW            \*
    %token T_DIV            /
    %skip  T_WHITESPACE     \s+

    #Expression
      : <T_DIGIT> (Operator() <T_DIGIT>)*
      ;

    #Operator
      : <T_PLUS>
      | <T_MINUS>
      | <T_POW>
      | <T_DIV>
      ;

EBNF);
```

### Execution

In order to quickly check the performance of what has been written, you can use
the simple `parse()` method. As a result, it will output the recognized abstract
syntax tree along with the predefined AST classes which can be converted to their
string representation.

```php
<?php

echo $compiler->parse('2 + 2');

//
// Output:
//
// <Expression offset="0">
//     <T_DIGIT offset="0">2</T_DIGIT>
//     <Operator offset="2">
//         <T_PLUS offset="2">+</T_PLUS>
//     </Operator>
//     <T_DIGIT offset="4">2</T_DIGIT>
// </Expression>
//
```

### Compilation

After your grammar is ready and tested, it should be compiled. After that,
you no longer need the `phplrt/compiler` dependency (see https://phplrt.org/docs/installation#runtime-only).

```php
file_put_contents(__DIR__ . '/grammar.php', (string)$compiler->build());
```

This file will contain your compiled data that can be used in your custom parser.

```php
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Parser;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;

$data = require __DIR__ . '/grammar.php';

// Create lexer from compiled data
$lexer = new Lexer($data['tokens']['default'], $data['skip']);

// Create parser from compiled data
$parser = new Parser($lexer, $data['grammar'], [

    // Recognition will start from the specified rule
    Parser::CONFIG_INITIAL_RULE => $data['initial'],

    // Rules for the abstract syntax tree builder.
    // In this case, we use the data found in the compiled grammar.
    Parser::CONFIG_AST_BUILDER => new class($data['reducers']) implements BuilderInterface {
        public function __construct(private array $reducers) {}

        public function build(ContextInterface $context, $result)
        {
            $state = $context->getState();

            return isset($this->reducers[$state]))
                ? $this->reducers[$state]($context, $result)
                : $result
            ;
        }
    }
]);

// Now we are ready to parse any code using the compiled grammar

$parser->parse(' ..... ');
```
