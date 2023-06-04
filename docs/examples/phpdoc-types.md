# PhpDoc Types

In this example, the grammar for describing types in phpdoc is compatible with
[psalm](https://psalm.dev/) and [phpstan](https://phpstan.org/), for example:

```php
array {
    field1: callable(Example, int): mixed,
    field2: list<Some>,
    field3: iterable<array-key, array{ int, non-empty-string }>,
    Some::CONST_*,
    ...
}
```

## Grammar

```pp2
// Literals

%token  T_DQ_STRING_LITERAL     "([^"\\]*(?:\\.[^"\\]*)*)"
%token  T_SQ_STRING_LITERAL     '([^'\\]*(?:\\.[^'\\]*)*)'
%token  T_FLOAT_LITERAL         (?i)(?:-?[0-9]++\.[0-9]*+(?:e-?[0-9]++)?)|(?:-?[0-9]*+\.[0-9]++(?:e-?[0-9]++)?)|(?:-?[0-9]++e-?[0-9]++)
%token  T_INT_LITERAL           \-?(?i)(?:(?:0b[0-1_]++)|(?:0o[0-7_]++)|(?:0x[0-9a-f_]++)|(?:[0-9][0-9_]*+))
%token  T_BOOL_LITERAL          \b(?i)(?:true|false)\b
%token  T_NULL_LITERAL          \b(?i)(?:null)\b

// Name

%token  T_NAME                  [a-zA-Z_\x80-\xff][a-zA-Z0-9\-_\x80-\xff]*

// Special Chars

%token  T_ANGLE_BRACKET_OPEN    <
%token  T_ANGLE_BRACKET_CLOSE   >
%token  T_PARENTHESIS_OPEN      \(
%token  T_PARENTHESIS_CLOSE     \)
%token  T_BRACE_OPEN            \{
%token  T_BRACE_CLOSE           \}
%token  T_SQUARE_BRACKET_OPEN   \[
%token  T_SQUARE_BRACKET_CLOSE  \]
%token  T_COMMA                 ,
%token  T_ELLIPSIS              \.\.\.
%token  T_DOUBLE_COLON          ::
%token  T_COLON                 :
%token  T_EQ                    =
%token  T_NS_DELIMITER          \\
%token  T_NULLABLE              \?
%token  T_NOT                   \!
%token  T_OR                    \|
%token  T_AND                   &
%token  T_ASTERISK              \*

// Other

%skip   T_WHITESPACE            \s+
%skip   T_BLOCK_COMMENT         \h*/\*.*?\*/\h*

%pragma root Statement

// Literals

Literal
  : StringLiteral()
  | FloatLiteral()
  | IntLiteral()
  | BoolLiteral()
  | NullLiteral()
  | ClassConstLiteral()
  ;

StringLiteral
  : <T_SQ_STRING_LITERAL>
  | <T_DQ_STRING_LITERAL>
  ;

FloatLiteral
  : <T_FLOAT_LITERAL>
  ;

IntLiteral
  : <T_INT_LITERAL>
  ;

BoolLiteral
  : <T_BOOL_LITERAL>
  ;

NullLiteral
  : <T_NULL_LITERAL>
  ;

ClassConstLiteral
  : Name() ::T_DOUBLE_COLON:: (<T_NAME><T_ASTERISK>|<T_NAME>|<T_ASTERISK>)
  ;

// Templates

TemplateParameters
  : ::T_ANGLE_BRACKET_OPEN::
      TemplateParameter() (::T_COMMA:: TemplateParameter())* ::T_COMMA::?
    ::T_ANGLE_BRACKET_CLOSE::
  ;

TemplateParameter
  : Statement()
  ;

// Shapes

ShapeArguments
  : ::T_BRACE_OPEN::
      ShapeArgument()? (::T_COMMA:: ShapeArgument())* ::T_COMMA::?
      IsSealed() ::T_COMMA::?
    ::T_BRACE_CLOSE::
  ;

IsSealed
  : <T_ELLIPSIS>?
  ;

ShapeArgument
  : OptionalNamedShapeArgument()
  | NamedShapeArgument()
  | AnonymousShapeArgument()
  ;

OptionalNamedShapeArgument
  : ShapeKey() ::T_NULLABLE:: ::T_COLON:: ShapeValue()
  ;

NamedShapeArgument
  : ShapeKey() ::T_COLON:: ShapeValue()
  ;

AnonymousShapeArgument
  : ShapeValue()
  ;

ShapeKey
  : <T_NAME>
  | IntLiteral()
  | BoolLiteral()
  | NullLiteral()
  | StringLiteral()
  ;

ShapeValue
  : Statement()
  ;

// Callables

CallableTypeStmt
  : Name()
    ::T_PARENTHESIS_OPEN::
        CallableArguments()?
    ::T_PARENTHESIS_CLOSE::
    CallableReturnType()?
  ;

CallableArguments
  : CallableArgument() (::T_COMMA:: CallableArgument())* ::T_COMMA::?
  ;

CallableArgument
  : PrefixedVariadicCallableArgument()
  ;

PrefixedVariadicCallableArgument
  : <T_ELLIPSIS> Statement()
  | SuffixedCallableArgument()
  ;

SuffixedCallableArgument
  : Statement() (<T_EQ> | <T_ELLIPSIS>)?
  ;

CallableReturnType
  : ::T_COLON:: Statement()
  ;

// Type definition

NamedTypeStmt
  : Name() (TemplateParameters() | ShapeArguments())?
  ;

// Other common rules

Name
  : FullQualifiedName()
  | RelativeName()
  ;

FullQualifiedName
  : ::T_NS_DELIMITER:: NamePart() (::T_NS_DELIMITER:: NamePart())*
  ;

RelativeName
  : NamePart() (::T_NS_DELIMITER:: NamePart())*
  ;

NamePart
  : <T_NAME>
  ;


/**
 * -----------------------------------------------------------------------------
 *  Constant Statement
 * -----------------------------------------------------------------------------
 *
 *  A constant statement can be evaluated during translation rather than
 *  runtime, and accordingly may be used in any place that a constant may be.
 *
 */

Statement
  : BinaryStatement()
  ;

// Binary statements/expressions

BinaryStatement
  : UnionTypeStatement()
  ;

UnionTypeStatement
  : IntersectionTypeStatement() (::T_OR:: UnionTypeStatement())?
  ;

IntersectionTypeStatement
  : UnaryStatement() (::T_AND:: IntersectionTypeStatement())?
  ;

// Unary statements/expressions

UnaryStatement
  : PrefixedNullableTypeStatement()
  ;

// stmt = ?Type
PrefixedNullableTypeStatement
  : <T_NULLABLE> TypesListStatement()
  | SuffixedNullableTypeStatement()
  ;

// stmt = Type?
SuffixedNullableTypeStatement
  : TypesListStatement() <T_NULLABLE>?
  ;

TypesListStatement
  : PrimaryStatement() (
      <T_SQUARE_BRACKET_OPEN>
      ::T_SQUARE_BRACKET_CLOSE::
    )*
  ;

// Primary

PrimaryStatement
  : ::T_PARENTHESIS_OPEN:: Statement() ::T_PARENTHESIS_CLOSE::
  | Literal()
  | CallableTypeStmt()
  | NamedTypeStmt()
  ;
```
