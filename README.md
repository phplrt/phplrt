<p align="center">
    <a href="https://phplrt.org/">
        <img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" />
    </a>
</p>
<p align="center">
    <a href="https://travis-ci.org/phplrt/phplrt"><img src="https://travis-ci.org/phplrt/phplrt.svg?branch=master" alt="Travis CI" /></a>
    <a href="https://codeclimate.com/github/phplrt/phplrt/test_coverage"><img src="https://api.codeclimate.com/v1/badges/90ee68ef959f72fe7bf6/test_coverage" /></a>
    <a href="https://codeclimate.com/github/phplrt/phplrt/maintainability"><img src="https://api.codeclimate.com/v1/badges/90ee68ef959f72fe7bf6/maintainability" /></a>
</p>
<p align="center">
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://img.shields.io/badge/PHP-7.4+-ff0140.svg" alt="PHP 7.1+"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/version" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/v/unstable" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/phplrt/phplrt"><img src="https://poser.pugx.org/phplrt/phplrt/downloads" alt="Total Downloads"></a>
    <a href="https://raw.githubusercontent.com/phplrt/phplrt/master/LICENSE.md"><img src="https://poser.pugx.org/phplrt/phplrt/license" alt="License MIT"></a>
</p>

<p align="center">
    <a href="https://opencollective.com/phplrt/donate?" target="_blank">
      <img src="https://opencollective.com/phplrt/donate/button@2x.png" width="200" />
    </a>
</p>

## Thanks To

<p align="justify">
    <a href="https://www.antlr.org/" target="_blank" rel="nofollow">
        <img src="https://phplrt.org/img/thanks/antlr-logo.png" alt="Antlr" height="48" />
    </a>
    <a href="https://hoa-project.net/" target="_blank" rel="nofollow">
        <img src="https://phplrt.org/img/thanks/hoa.svg" alt="Hoa Project" height="48" />
    </a>
    <a href="https://github.com/nikic/PHP-Parser" target="_blank" rel="nofollow">
        <img src="https://phplrt.org/img/thanks/php-parser.png" alt="nikic/PHP-Parser" height="48" />
    </a>
    <a href="https://www.jetbrains.com/" target="_blank" rel="nofollow">
        <img src="https://phplrt.org/img/thanks/jetbrains.svg" alt="JetBrains" height="64" />
    </a>
</p>

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

More detailed installation instructions [are here](/docs/installation).

## Quick Start

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

### Compilation

```php
\file_put_contents(__DIR__ . '/grammar.php', (string)$compiler->build());
```
