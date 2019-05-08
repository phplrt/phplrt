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
use Phplrt\Parser\Grammar;
use Phplrt\Parser\Rule\{Concatenation, Alternation, Terminal};

$parser = new Grammar([
    new Concatenation(0, [8, 6, 7], 'expression'),  // expression = T_NUMBER operation ( ... ) ;
    new Alternation(7, [8, 0]),                     // ( T_NUMBER | expression ) ;
    new Alternation(6, [1, 2], 'operation'),        // operation = T_PLUS | T_MINUS ;
    new Terminal(8, 'T_NUMBER', true),
    new Terminal(1, 'T_PLUS', true),
    new Terminal(2, 'T_MINUS', true),
], 'expression');
```

## Example

Execution:

```php
echo $parser->parse(File::fromSources('2 + 2 - 10 + 1000'));
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
