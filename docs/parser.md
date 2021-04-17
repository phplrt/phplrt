# Parser

> This package can be installed separately using the command `composer require phplrt/parser`

The parser provides a set of components for grammar analysis (Parsing) of the source code 
and converting them into an abstract syntax tree (AST).

Let's [create a primitive lexer](/docs/lexer) that can handle spaces, 
numbers and the addition character.

```php
use Phplrt\Lexer\Lexer;

$lexer = (new Lexer())
    ->append('T_NUMBER', '\\d+')
    ->append('T_PLUS', '\\+')
    ->append('T_WHITESPACE', '\\s+')
        ->skip('T_WHITESPACE')
;
```

Grammar will be a little more complicated. We need to determine in what order 
the tokens in the source text can be located, which we will parse.

First we start with the [(E)BNF format](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form):

```ebnf
(* A simple example of adding two numbers will look like this: *)
expr = T_NUMBER T_PLUS T_NUMBER ;
```

To define this rule inside the Grammar, we simply use two classes that define the rules 
inside the product, this is the [concatenation](https://en.wikipedia.org/wiki/Concatenation) 
and definitions of the tokens.

```php
use Phplrt\Parser\Grammar\Concatenation;use Phplrt\Parser\Grammar\Lexeme;use Phplrt\Parser\Parser;

$options = [Parser::CONFIG_INITIAL_RULE => 'expression'];

//
// This (e)BNF construction:
// expression = T_NUMBER T_PLUS T_NUMBER ;
// 
// Looks like:
// Concatenation = Token1 Token2 Token1
//
$grammar = [
    // Concat: 1 then 2 then 1
    'expression' => new Concatenation([1, 2, 1]),

    // 1 is a T_NUMBER token
    1 => new Lexeme('T_NUMBER'),

    // 2 is a T_PLUS lexeme
    2 => new Lexeme('T_PLUS'),
];
```

In order to test the grammar, we can simply parse the source.

```php

$parser = new \Phplrt\Parser\Parser($lexer, $grammar, $options);

var_dump($parser->parse('2 + 2'));
```

But if the source is wrong, the parser will tell you 
exactly where the error occurred:

```php
$result = $parser->parse('2 + + 2');
// Syntax error, unexpected "+" (T_PLUS)
//   1. | 2 + + 2
//      |     ^ in .../Parser/src/Exception/UnexpectedTokenException.php:37
```
