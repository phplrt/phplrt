# Examples

A more complex example of a math:

## (E)BNF

```ebnf
expression = T_NUMBER operation ( T_NUMBER | expression ) ;
operation = T_PLUS | T_MINUS ;
```

## PP2

```ebnf
%token  T_NUMBER        \d+
%token  T_PLUS          \+
%token  T_MINUS         \-
%skip   T_WHITESPACE    \s+

#expression
  : <T_NUMBER> operation() (<T_NUMBER> | expression())
  ;

#operation
 : <T_PLUS> 
 | <T_MINUS>
 ;
```

## PHP

```php
<?php
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Rule\{Concatenation, Alternation, Lexeme};

$lexer = new Lexer([
    'T_NUMBER'      => '\d+',
    'T_PLUS'        => '\+',
    'T_MINUS'       => '\-',
    'T_WHITESPACE'  => '\s+',
], [
    'T_WHITESPACE'
]);

/**
 * expression
 *   : <T_NUMBER> operation() ( expression() | <T_NUMBER> )
 *   ;
 * 
 * operation
 *  : <T_PLUS> 
 *  | <T_MINUS>
 *  ;
 */

$parser = new Parser($lexer, [
    0 => new Concatenation([3, 2, 1]),  // expression = T_NUMBER operation ( ... )
    1 => new Alternation([0, 3]),       // ( expression | T_NUMBER )
    2 => new Alternation([4, 5]),       // operation = T_PLUS | T_MINUS
    3 => new Lexeme('T_NUMBER', true),
    4 => new Lexeme('T_PLUS', true),
    5 => new Lexeme('T_MINUS', true),
], [Parser::CONFIG_INITIAL_RULE => 0]);
```

## Example

Execution:

```php
echo $parser->parse('2 + 2 - 10 + 1000');
```

Result:

```xml
<Ast>
  <expression offset="0">
    <T_NUMBER offset="0">2</T_NUMBER>
    <operation offset="2">
      <T_PLUS offset="2">+</T_PLUS>
    </operation>
    <expression offset="4">
      <T_NUMBER offset="4">2</T_NUMBER>
      <operation offset="6">
        <T_MINUS offset="6">-</T_MINUS>
      </operation>
      <expression offset="8">
        <T_NUMBER offset="8">10</T_NUMBER>
        <operation offset="11">
          <T_PLUS offset="11">+</T_PLUS>
        </operation>
        <T_NUMBER offset="13">1000</T_NUMBER>
      </expression>
    </expression>
  </expression>
</Ast>
```
