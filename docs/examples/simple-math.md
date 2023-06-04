# Simple Math Expression

A more complex example of an addition and subtraction expressions, like:

```php
2 + 2 - 42
```

## Grammar

```pp2
%token  T_NUMBER        \d+
%token  T_PLUS          \+
%token  T_MINUS         \-
%skip   T_WHITESPACE    \s+

%pragma root Expression

Expression
  : <T_NUMBER> Operation() (Expression() | <T_NUMBER>)
  ;

Operation
  : <T_PLUS>
  | <T_MINUS>
  ;
```
