# Rule Engine

A rule language of the kind that ends up in the database of every application
with permissions or discounts in it - a condition written by someone who is
not going to deploy PHP to change it:

```
group in ['admin', 'moderator'] and points > 30
user.group.name = 'admin' and user.points() >= 100
count(user.roles) > 0 xor is_active(user, now())
```

The grammar is a precedence ladder, one rule per level: `Disjunction` for
`or` and `xor`, `Conjunction` for `and`, `Operation` for everything that
compares. Each is written in terms of the one below it, which is all that
"binds tighter" ever means in a grammar.

Then comes the good part. The comparison operators are not in the grammar at
all: `T_IDENTIFIER` is "anything but the characters written around it", so
`=`, `>=` and `in` are read as ordinary names, and `Operation` reads *some*
name between two operands. Adding an operator to this language is a change to
the evaluator and not to the grammar.

## Grammar

```pp3
/**
 * -----------------------------------------------------------------------------
 *  Hoa Ruler
 * -----------------------------------------------------------------------------
 *
 * A rule language: a condition written of the values it compares, the
 * operators joining them and the functions it calls.
 *
 * @see https://github.com/hoaproject/Ruler/blob/master/Grammar.pp
 */

%pragma root Expression

%skip  T_WHITESPACE           \s++

%token T_TRUE                 (?i)true\b
%token T_FALSE                (?i)false\b
%token T_NULL                 (?i)null\b
%token T_NOT                  (?i)not\b
%token T_AND                  (?i)and\b
%token T_OR                   (?i)or\b
%token T_XOR                  (?i)xor\b

// A quote written inside a string is escaped with a backslash
%token T_STRING               "(?:[^"\\]|\\.)*+"|'(?:[^'\\]|\\.)*+'

%token T_FLOAT                [0-9]++\.[0-9]++
%token T_INTEGER              [0-9]++

%token T_PARENTHESIS_OPEN     \(
%token T_PARENTHESIS_CLOSE    \)
%token T_BRACKET_OPEN         \[
%token T_BRACKET_CLOSE        \]
%token T_COMMA                ,
%token T_DOT                  \.

// Anything but the characters written around it, an operator included
%token T_IDENTIFIER           [^\s()\[\],.]++

/**
 * -----------------------------------------------------------------------------
 *  Expression
 * -----------------------------------------------------------------------------
 *
 *  One rule per precedence, from the loosest binding one down to the tightest:
 *  - a or b, a xor b
 *  - a and b
 *  - a = b, a in b - the operator is written as a name of its own
 */

Expression
  : Disjunction()
  ;

Disjunction
  : Conjunction() ((::T_OR:: | ::T_XOR::) Disjunction())?
  ;

Conjunction
  : Operation() (::T_AND:: Conjunction())?
  ;

Operation
  : Operand() (<T_IDENTIFIER> Disjunction())?
  ;

Operand
  : ::T_PARENTHESIS_OPEN:: Disjunction() ::T_PARENTHESIS_CLOSE::
  | Value()
  ;

Value
  : ::T_NOT:: Disjunction()
  | <T_TRUE>
  | <T_FALSE>
  | <T_NULL>
  | <T_FLOAT>
  | <T_INTEGER>
  | <T_STRING>
  | ArrayDeclaration()
  | Chain()
  ;

// ["a", "b"]
ArrayDeclaration
  : ::T_BRACKET_OPEN:: Value() (::T_COMMA:: Value())* ::T_BRACKET_CLOSE::
  ;

// user.group[0].name(), points
Chain
  : (FunctionCall() | Variable()) (ArrayAccess() | ObjectAccess())*
  ;

Variable
  : <T_IDENTIFIER>
  ;

ArrayAccess
  : ::T_BRACKET_OPEN:: Value() ::T_BRACKET_CLOSE::
  ;

ObjectAccess
  : ::T_DOT:: (FunctionCall() | <T_IDENTIFIER>)
  ;

FunctionCall
  : <T_IDENTIFIER> ::T_PARENTHESIS_OPEN::
      (Disjunction() (::T_COMMA:: Disjunction())*)?
    ::T_PARENTHESIS_CLOSE::
  ;
```

## Usage

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

$rule = $parser->parse(new Source("group in ['admin'] and points > 30"));
```

`Chain` is the other half of the appeal: `user.group[0].name()` is read as a
variable followed by a list of accesses, so the evaluator walks it against a
context array or object without the grammar knowing anything about either.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
