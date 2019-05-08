# Parser

The parser provides a set of components for grammar analysis (Parsing) of the source code 
and converting them into an abstract syntax tree (AST).

For the beginning it is necessary to familiarize with parsing algorithms. This implementation,
although it allows you to switch between runtime, but provides out of the box two 
implementations: [LL(1) - Simple and LL(k) - Lookahead](https://en.wikipedia.org/wiki/LL_parser).

In order to create your own parser we need:
1) Create [lexer](#lexer)
2) Create [grammar](#grammar)
3) Create [parser](#parser)

## Lexer

Let's create a primitive lexer that can handle spaces, 
numbers and the addition character.

```php
use Phplrt\Lexer\Driver\NativeRegex as Lexer;

$lexer = (new Lexer())
    ->add('T_WHITESPACE', '\\s+')
    ->add('T_NUMBER', '\\d+')
    ->add('T_PLUS', '\\+')
    ->skip('T_WHITESPACE'); 
```

## Grammar

Grammar will be a little more complicated. We need to determine in what order 
the tokens in the source text can be located, which we will parse.

```php
$grammar = new Grammar(array $rules[, string|int $rootRuleId = null [, array $delegates = []]])
```

First we start with the [(E)BNF format](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form):

```ebnf
(* A simple example of adding two numbers will look like this: *)
expr = T_NUMBER T_PLUS T_NUMBER ;
```

To define this rule inside the Grammar, we simply use two classes that define the rules 
inside the product, this is the [concatenation](https://en.wikipedia.org/wiki/Concatenation) 
and definitions of the tokens.

```php
use Phplrt\Parser\Grammar;
use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Parser\Rule\Terminal;

//
// This (e)BNF construction:
// expression = T_NUMBER T_PLUS T_NUMBER ;
// 
// Looks like:
// Concatenation1 = Token1 Token2 Token1
//
$grammar = new Grammar([
    new Concatenation(0, [1, 2, 1], 'expression'),
    new Terminal(1, 'T_NUMBER', true),
    new Terminal(2, 'T_PLUS', true),
]);
```

## Parsing

In order to test the grammar, we can simply parse the source.

```php
use Phplrt\Io\File;
use Phplrt\Parser\Driver\Llk as Parser;

$parser = new Parser($lexer, $grammar);

echo $parser->parse(File::fromSources('2 + 2'));
```

Will outputs:

```xml
<Ast>
    <expression offset="0">
        <T_NUMBER offset="0">2</T_NUMBER>
        <T_PLUS offset="2">+</T_PLUS>
        <T_NUMBER offset="4">2</T_NUMBER>
    </expression>
</Ast>
```

But if the source is wrong, the parser will tell you 
exactly where the error occurred:

```php
echo $parser->parse(File::fromSources('2 + + 2'));
//                                         ^
//
// throws "Phplrt\Parser\Exception\UnexpectedTokenException" with message: 
// "Unexpected token '+' (T_PLUS) at line 1 and column 5"
```
