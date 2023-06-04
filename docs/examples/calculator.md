# Calculator Example

Below is an example of a simple calculator.

## Grammar

```pp2
%pragma root Expression

%token T_INT      \d+
%token T_FLOAT    \d+\.\d+

%token T_PLUS     \+
%token T_MINUS    \-
%token T_MUL      \*
%token T_DIV      [/÷]

%token T_BRACE_OPEN   \(
%token T_BRACE_CLOSE  \)

%skip  T_WHITESPACE \s+

Expression
  : Addition()
  | Subtraction()
  | Term()
  ;

Term
  : Multiplication()
  | Division()
  | Factor()
  ;

Factor
  : ::T_BRACE_OPEN:: Expression() ::T_BRACE_CLOSE::
  | Value()
  ;

#Subtraction -> {
    return new \Ast\Subtraction($children, $token->getOffset());
}
  : Term() ::T_MINUS:: Expression()
  ;

#Addition -> {
    return new \Ast\Addition($children, $token->getOffset());
}
  : Term() ::T_PLUS:: Expression()
  ;

#Multiplication -> {
    return new \Ast\Multiplication($children, $token->getOffset());
}
  : Factor() ::T_MUL:: Term()
  | Factor() Term()
  ;

#Division -> {
    return new \Ast\Division($children, $token->getOffset());
}
  : Factor() ::T_DIV:: Term()
  ;

#Value -> {
    return new \Ast\Value($children, $token->getOffset());
}
  : <T_FLOAT>
  | <T_INT>
  ;
```

Please note that you need to implement 4 expression AST nodes:
- `Ast\Subtraction`
- `Ast\Addition`
- `Ast\Multiplication`
- `Ast\Division`

And one AST node that will be meaning the value:
- `Ast\Value`

## Execution

```php
<?php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

$compiler = new Compiler();
$compiler->load(File::fromPathname('path/to/grammar-file.pp2'));

echo $compiler->parse('2 + 2 * 2');
```

### Result

```xml
<Addition offset="0">
    <Value offset="0">
        <T_INT offset="0">2</T_INT>
    </Value>
    <Multiplication offset="4">
        <Value offset="4">
            <T_INT offset="4">2</T_INT>
        </Value>
        <Value offset="8">
            <T_INT offset="8">2</T_INT>
        </Value>
    </Multiplication>
</Addition>
```
