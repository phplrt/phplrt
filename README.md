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
    - [Compilation](docs/compiler.md#parser-compilation)
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
