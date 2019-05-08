# PHPLRT

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

The phplrt is a set of tools for programming languages recognition. The library 
provides lexer, parser, grammar compiler, library for working with errors, 
text analysis and so on.

- [Installation](installation.md)
- [Grammar](grammar.md)
    - [Definitions](grammar.md#definitions)
    - [Comments](grammar.md#comments)
    - [Output Control](grammar.md#output-control)
    - [Declaring Rules](grammar.md#declaring-rules)
    - [Delegates](grammar.md#delegation)
- [Grammar Compiler](compiler.md)
    - [Compilation](compiler.md#parser-compilation)
- [Lexer](lexer.md)
- [Lexer Drivers](drivers.md)
    - [Basic](drivers.md#basic)
    - [Multistate](drivers.md#multistate)
- [Parser](parser.md#parser)
    - [Lexer](parser.md#lexer)
    - [Grammar](parser.md#grammar)
    - [Parsing](parser.md#parsing)
- [Parser Rules](rules.md#rules)
    - [Alternation](rules.md#alternation)
    - [Concatenation](rules.md#concatenation)
    - [Repetition](rules.md#repetition)
    - [Terminal](rules.md#terminal)
- [Parser Examples](examples.md#examples)
- [Abstract Syntax Tree](ast.md#abstract-syntax-tree)
- [Delegates](delegates.md#delegates)
