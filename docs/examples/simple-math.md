# Simple Math Expression

A more complex example of a math:

## Grammar

```ebnf
%token  T_NUMBER        \d+
%token  T_PLUS          \+
%token  T_MINUS         \-
%skip   T_WHITESPACE    \s+

#Expression
  : <T_NUMBER> Operation() (Expression() | <T_NUMBER>)
  ;

#Operation
  : <T_PLUS> 
  | <T_MINUS>
  ;
```

## Execution

```php
<?php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

$compiler = new Compiler();
$compiler->load(File::fromPathname('path/to/grammar-file.pp2'));

echo $compiler->parse('2 + 2 * 2');
```

## Result

```xml
<Expression offset="0">
    <T_NUMBER offset="0">2</T_NUMBER>
    <Operation offset="2">
        <T_PLUS offset="2">+</T_PLUS>
    </Operation>
    <Expression offset="4">
        <T_NUMBER offset="4">2</T_NUMBER>
        <Operation offset="6">
            <T_MINUS offset="6">-</T_MINUS>
        </Operation>
        <Expression offset="8">
            <T_NUMBER offset="8">10</T_NUMBER>
            <Operation offset="11">
                <T_PLUS offset="11">+</T_PLUS>
            </Operation>
            <T_NUMBER offset="13">1000</T_NUMBER>
        </Expression>
    </Expression>
</Expression>
```
