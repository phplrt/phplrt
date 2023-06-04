# Calculator Example

This example provides a grammar for creating full-fledged mathematical
expressions, including multiplication and division, that take into account
operator precedence, like:

```php
(2 + 2) * 2 - 4 - 4 + 42 * 10
```

## Grammar

```pp2
%token  T_FLOAT         \d+\.\d+
%token  T_INT           \d+

%token  T_PLUS          \+
%token  T_MINUS         \-
%token  T_MUL           \*
%token  T_DIV           /

%token  T_BRACE_OPEN    \(
%token  T_BRACE_CLOSE   \)

%skip   T_WHITESPACE    \s+

%pragma root Expression

Expression
  : BinaryExpression()
  ;

BinaryExpression
  : AdditiveExpression()
  ;

AdditiveExpression
  : (MultiplicativeExpression() (<T_PLUS>|<T_MINUS>))* 
    MultiplicativeExpression()
  ;

MultiplicativeExpression
  : (UnaryExpression() (<T_DIV>|<T_MUL>))* 
    UnaryExpression()
  ;

UnaryExpression
  : ::T_BRACE_OPEN:: Expression() ::T_BRACE_CLOSE::
  | Literal()
  ;

Literal
  : <T_FLOAT>
  | <T_INT>
  ;
```
