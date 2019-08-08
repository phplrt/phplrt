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

## Introduction

The phplrt is a set of tools for programming languages recognition. The library 
provides lexer, parser, grammar compiler, library for working with errors, 
text analysis and so on.

## Documentation

- [Installation](docs/installation.md)
- [Grammar](docs/grammar.md)
    - [Definitions](docs/grammar.md#definitions)
    - [Comments](docs/grammar.md#comments)
    - [Output Control](docs/grammar.md#output-control)
    - [Declaring Rules](docs/grammar.md#declaring-rules)
    - [Delegates](docs/grammar.md#delegation)
- [Compiler](docs/compiler.md)
    - [Loading](docs/compiler.md#loading)
    - [Compilation](docs/compiler.md#compilation)
- [Lexer](docs/lexer.md)
- [Parser](docs/parser.md#parser)
- [Drivers](docs/drivers.md)
    - [PCRE](docs/drivers.md#pcre)
    - [Lexrtl](docs/drivers.md#lexertl)
- [Rules](docs/rules.md#rules)
    - [Alternation](docs/rules.md#alternation)
    - [Concatenation](docs/rules.md#concatenation)
    - [Repetition](docs/rules.md#repetition)
    - [Terminal](docs/rules.md#terminal)
- [Examples](docs/examples.md#examples)
- [Abstract Syntax Tree](docs/ast.md#abstract-syntax-tree)
- [Delegates](docs/delegates.md#delegates)
- [Assembler](docs/assembler.md)

## Quickstart

```php
<?php
use Phplrt\Source\File;
use Phplrt\Compiler\Compiler;

$compiler = Compiler::load(File::fromSources(<<<EBNF
   
    %token T_DIGIT      \d
    %token T_PLUS       \+
    %token T_MINUS      \-
    %token T_POW        \*
    %token T_DIV        \/
    %skip  T_WHITESPACE \s+
    
    #Expression
      : <T_DIGIT> (::T_PLUS:: | ::T_MINUS:: | ::T_POW:: | ::T_DIV::) <T_DIGIT> 
      ;

EBNF));
```

**Execution:**

```php
echo $compiler->parse(File::fromSources('2 + 2'));

//
// Output:
//
// <Expression offset="0">
//   <T_DIGIT offset="0">2</T_DIGIT>
//   <T_DIGIT offset="2">2</T_DIGIT>
// </Expression>
//
```

**Compilation:**

```php
$compiler
    ->setNamespace('App')
    ->setClassName('ExpressionParser')
    ->saveTo(__DIR__);
```

## Stability

### Contracts

| Package                                                                                 | Stability                                                                                |
| --------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| [phplrt/ast-contracts](https://packagist.org/packages/phplrt/ast-contracts)             | ![phplrt/ast-contracts](https://poser.pugx.org/phplrt/ast-contracts/version)             |
| [phplrt/exception-contracts](https://packagist.org/packages/phplrt/exception-contracts) | ![phplrt/exception-contracts](https://poser.pugx.org/phplrt/exception-contracts/version) |
| [phplrt/lexer-contracts](https://packagist.org/packages/phplrt/lexer-contracts)         | ![phplrt/lexer-contracts](https://poser.pugx.org/phplrt/lexer-contracts/version)         |
| [phplrt/parser-contracts](https://packagist.org/packages/phplrt/parser-contracts)       | ![phplrt/parser-contracts](https://poser.pugx.org/phplrt/parser-contracts/version)       |
| [phplrt/source-contracts](https://packagist.org/packages/phplrt/source-contracts)       | ![phplrt/source-contracts](https://poser.pugx.org/phplrt/source-contracts/version)       |

### Packages

| Package                                                             | Stability                                                            |
| ------------------------------------------------------------------- | -------------------------------------------------------------------- |
| [phplrt/assembler](https://packagist.org/packages/phplrt/assembler) | ![phplrt/compiler](https://poser.pugx.org/phplrt/assembler/version)  |
| [phplrt/ast](https://packagist.org/packages/phplrt/ast)             | ![phplrt/ast](https://poser.pugx.org/phplrt/ast/version)             |
| [phplrt/compiler](https://packagist.org/packages/phplrt/compiler)   | ![phplrt/compiler](https://poser.pugx.org/phplrt/compiler/version)   |
| [phplrt/dumper](https://packagist.org/packages/phplrt/dumper)       | ![phplrt/dumper](https://poser.pugx.org/phplrt/dumper/version)       |
| [phplrt/exception](https://packagist.org/packages/phplrt/exception) | ![phplrt/exception](https://poser.pugx.org/phplrt/exception/version) |
| [phplrt/lexer](https://packagist.org/packages/phplrt/lexer)         | ![phplrt/lexer](https://poser.pugx.org/phplrt/lexer/version)         |
| [phplrt/parser](https://packagist.org/packages/phplrt/parser)       | ![phplrt/parser](https://poser.pugx.org/phplrt/parser/version)       |
| [phplrt/position](https://packagist.org/packages/phplrt/position)   | ![phplrt/position](https://poser.pugx.org/phplrt/position/version)   |
| [phplrt/source](https://packagist.org/packages/phplrt/source)       | ![phplrt/source](https://poser.pugx.org/phplrt/source/version)       |
| [phplrt/visitor](https://packagist.org/packages/phplrt/visitor)     | ![phplrt/visitor](https://poser.pugx.org/phplrt/visitor/version)     |

### Deprecated Packages

| Package                                                       | Stability                                                      |
| ------------------------------------------------------------- | -------------------------------------------------------------- |
| [phplrt/io](https://packagist.org/packages/phplrt/io)         | ![phplrt/io](https://poser.pugx.org/phplrt/io/version)         |
| [phplrt/stream](https://packagist.org/packages/phplrt/stream) | ![phplrt/stream](https://poser.pugx.org/phplrt/stream/version) |
