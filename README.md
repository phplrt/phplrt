<p align="center">
    <a href="https://railt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>
<p align="center">
    <a href="https://travis-ci.org/phplrt/phplrt"><img src="https://travis-ci.org/phplrt/phplrt.svg?branch=master" alt="Travis CI" /></a>
    <a href="https://codeclimate.com/github/phplrt/phplrt/test_coverage"><img src="https://api.codeclimate.com/v1/badges/90ee68ef959f72fe7bf6/test_coverage" /></a>
    <a href="https://codeclimate.com/github/phplrt/phplrt/maintainability"><img src="https://api.codeclimate.com/v1/badges/90ee68ef959f72fe7bf6/maintainability" /></a>
</p>
<p align="center">
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://img.shields.io/badge/PHP-7.1+-ff0140.svg" alt="PHP 7.1+"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/version" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/v/unstable" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/downloads" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/phplrt/phplrt/master/LICENSE.md"><img src="https://poser.pugx.org/phplrt/phplrt/license" alt="License MIT"></a>
</p>

> Please note that this is phplrt `2.0.x-dev` documentation.* 
> The latest stable version is here: 
> [https://github.com/phplrt/phplrt/tree/1.1.0](https://github.com/phplrt/phplrt/tree/1.1.0)

## Introduction

The phplrt is a set of tools for programming languages recognition. The library 
provides lexer, parser, grammar compiler, library for working with errors, 
text analysis and so on.

## Documentation

- [Installation](docs/installation.md)
- [Compiler](docs/compiler.md)
    - [Loading](docs/compiler.md#loading)
    - [Compilation](docs/compiler.md#compilation)
    - [Grammar](docs/grammar.md)
        - [Definitions](docs/grammar.md#definitions)
        - [Comments](docs/grammar.md#comments)
        - [Output Control](docs/grammar.md#output-control)
        - [Declaring Rules](docs/grammar.md#declaring-rules)
        - [Delegates](docs/grammar.md#delegation)
- [Lexer](docs/lexer.md)
- [Parser](docs/parser.md#parser)
    - [Exception Handling](docs/parser.md#exception-handling)
    - [Rules](docs/rules.md#rules)
        - [Alternation](docs/rules.md#alternation)
        - [Concatenation](docs/rules.md#concatenation)
        - [Repetition](docs/rules.md#repetition)
        - [Optional](docs/rules.md#optional)
        - [Lexeme](docs/rules.md#lexeme)
- [Examples](docs/examples.md#examples)
- [Abstract Syntax Tree](docs/ast.md)
    - [Builder](docs/ast.md#ast-builder)

## Quickstart

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

**Execution:**

```php
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

**Compilation:**

```php
$compiler
    ->build('App\\ExpressionParser')
    ->save(__DIR__ . '/ExpressionParser.php')
;
```
