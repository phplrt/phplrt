# PhpDoc Types

The type language of [phpstan](https://phpstan.org) and
[psalm](https://psalm.dev) - the one hiding inside `@param`, `@return` and
`@var`, which has quietly grown into a language with generics, shapes and
conditionals:

```
array{id: positive-int, nickname?: ?non-empty-string, handler: callable(int): void}

\Closure<TKey of array-key, TValue super Demo\Entity\User>(TKey $key, int ...$rest): TValue

($size is positive-int ? non-empty-array<int, User> : Demo\Status::ACTIVE)
```

A fully qualified name is one token, not a rule. `\Demo\Entity\User` and
`non-empty-string` are both read by `T_IDENTIFIER` in a single step, so the
backslash and the dash never reach the parser and never have to be told apart
from an operator.

Above it, `Type` reads an `Atomic` and then a chain of unions *or* a chain of
intersections, never both. `A|B|C` and `A&B` are read; `A|B&C` is refused
until the brackets say how it groups, which is the reading
`phpstan/phpdoc-parser` insists on as well.

The line to steal is at the top, though: `%pragma lexer.pcre.disable u` turns
the `u` modifier off for the whole lexer. Identifiers here are written of
bytes rather than of code points, which is what lets `\x80-\xff` stand for
"any character a name outside ASCII is built from".

## Grammar

```pp3
/**
 * -----------------------------------------------------------------------------
 *  PHPDoc Type Expression Of PHPStan
 * -----------------------------------------------------------------------------
 *
 * The type language read by "phpstan/phpdoc-parser".
 *
 * @see https://github.com/phpstan/phpdoc-parser/blob/2.3.x/doc/grammars/type.abnf
 */

// An identifier is written of octets, so "\x80-\xff" names every byte a name
// outside ASCII is written of
%pragma lexer.pcre.disable u

%pragma root Type

/**
 * -----------------------------------------------------------------------------
 *  Lexemes
 * -----------------------------------------------------------------------------
 */

%skip  T_WHITESPACE             \s++

%token T_CONSTANT_FLOAT         [+\-]?(?:[0-9]++(?:_[0-9]++)*+\.(?:[0-9]++(?:_[0-9]++)*+)?(?:[eE][+\-]?[0-9]++(?:_[0-9]++)*+)?|[0-9]++(?:_[0-9]++)*+(?:[eE][+\-]?[0-9]++(?:_[0-9]++)*+)|\.[0-9]++(?:_[0-9]++)*+(?:[eE][+\-]?[0-9]++(?:_[0-9]++)*+)?)
%token T_CONSTANT_INT           [+\-]?(?:0[bB][01]++(?:_[01]++)*+|0[oO][0-7]++(?:_[0-7]++)*+|0[xX][0-9a-fA-F]++(?:_[0-9a-fA-F]++)*+|[0-9]++(?:_[0-9]++)*+)
%token T_CONSTANT_STRING        '(?:\\[^\r\n]|[^\r\n\\'])*+'|"(?:\\[^\r\n]|[^\r\n\\"])*+"

%token T_THIS_VARIABLE          \$this(?![a-zA-Z0-9_\x80-\xff])
%token T_VARIABLE               \$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*+

// The words a template argument, a variance and a conditional are written of:
// "T of User", "covariant T", "T is not int ? A : B"
%token T_CONTRAVARIANT          contravariant(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_COVARIANT              covariant(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_SUPER                  super(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_NOT                    not(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_IS                     is(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_OF                     of(?![a-zA-Z0-9_\-\\\x80-\xff])

// Every part of a name at once: "\Demo\Entity\User", "non-empty-string"
%token T_IDENTIFIER             \\?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\-\x80-\xff]*+(?:\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\-\x80-\xff]*+)*+

%token T_VARIADIC               \.\.\.
%token T_DOUBLE_COLON           ::
%token T_COLON                  :
%token T_EQUAL_SIGN             =
%token T_UNION                  \|
%token T_INTERSECTION           &
%token T_NULLABLE               \?
%token T_WILDCARD               \*
%token T_COMMA                  ,

%token T_PARENTHESES_OPEN       \(
%token T_PARENTHESES_CLOSE      \)
%token T_ANGLE_BRACKET_OPEN     <
%token T_ANGLE_BRACKET_CLOSE    >
%token T_SQUARE_BRACKET_OPEN    \[
%token T_SQUARE_BRACKET_CLOSE   \]
%token T_CURLY_BRACKET_OPEN     \{
%token T_CURLY_BRACKET_CLOSE    \}

/**
 * -----------------------------------------------------------------------------
 *  Type
 * -----------------------------------------------------------------------------
 */

Type
  : Atomic() (Union() | Intersection())?
  | Nullable()
  ;

// A type written inside a pair of parentheses, where a conditional is written
ParenthesizedType
  : <T_VARIABLE> Conditional()
  | Atomic() (Union() | Intersection() | Conditional())?
  | Nullable()
  ;

Union
  : (::T_UNION:: Atomic())+
  ;

Intersection
  : (::T_INTERSECTION:: Atomic())+
  ;

// is int ? string : bool
Conditional
  : ::T_IS:: <T_NOT>? Atomic()
    ::T_NULLABLE:: Type()
    ::T_COLON:: ParenthesizedType()
  ;

// ?int
Nullable
  : ::T_NULLABLE:: Atomic()
  ;

Atomic
  : <T_CONSTANT_FLOAT>
  | <T_CONSTANT_INT>
  | <T_CONSTANT_STRING>
  | <T_THIS_VARIABLE>
  | ::T_PARENTHESES_OPEN:: ParenthesizedType() ::T_PARENTHESES_CLOSE:: Array()?
  | Identifier() (
      Callable()
    | Generic()
    | ArrayShape()
    | Array()
    | ClassConstant()
    )?
  ;

Identifier
  : <T_IDENTIFIER>
  | <T_IS>
  | <T_NOT>
  | <T_OF>
  | <T_SUPER>
  | <T_COVARIANT>
  | <T_CONTRAVARIANT>
  ;

// Demo\Status::ACTIVE
ClassConstant
  : ::T_DOUBLE_COLON:: Identifier()
  ;

/**
 * -----------------------------------------------------------------------------
 *  Generic
 * -----------------------------------------------------------------------------
 */

// <int, non-empty-string>
Generic
  : ::T_ANGLE_BRACKET_OPEN::
      GenericTypeArgument() (::T_COMMA:: GenericTypeArgument())*
    ::T_ANGLE_BRACKET_CLOSE::
  ;

GenericTypeArgument
  : (<T_CONTRAVARIANT> | <T_COVARIANT>)? Type()
  | <T_WILDCARD>
  ;

/**
 * -----------------------------------------------------------------------------
 *  Callable
 * -----------------------------------------------------------------------------
 */

// <T of User>(T $value, int ...$rest): T
Callable
  : CallableTemplate()?
    ::T_PARENTHESES_OPEN:: CallableParameters()? ::T_PARENTHESES_CLOSE::
    ::T_COLON:: CallableReturnType()
  ;

CallableTemplate
  : ::T_ANGLE_BRACKET_OPEN::
      CallableTemplateArgument() (::T_COMMA:: CallableTemplateArgument())*
    ::T_ANGLE_BRACKET_CLOSE::
  ;

CallableTemplateArgument
  : Identifier()
    (::T_OF:: Type())?
    (::T_SUPER:: Type())?
    (::T_EQUAL_SIGN:: Type())?
  ;

CallableParameters
  : CallableParameter() (::T_COMMA:: CallableParameter())*
  ;

// The "&" of a parameter read by reference, the "..." of a variadic one, the
// name it is called by and the "=" of an optional one
CallableParameter
  : Type()
    <T_INTERSECTION>?
    <T_VARIADIC>?
    <T_VARIABLE>?
    <T_EQUAL_SIGN>?
  ;

CallableReturnType
  : Identifier() Generic()?
  | Nullable()
  | ::T_PARENTHESES_OPEN:: Type() ::T_PARENTHESES_CLOSE::
  ;

/**
 * -----------------------------------------------------------------------------
 *  Array And Shape
 * -----------------------------------------------------------------------------
 */

// [][]
Array
  : (::T_SQUARE_BRACKET_OPEN:: ::T_SQUARE_BRACKET_CLOSE::)+
  ;

// {id: int, name?: string, 0: bool}
ArrayShape
  : ::T_CURLY_BRACKET_OPEN::
      ArrayShapeItem() (::T_COMMA:: ArrayShapeItem())*
    ::T_CURLY_BRACKET_CLOSE::
  ;

ArrayShapeItem
  : ArrayShapeKey() <T_NULLABLE>? ::T_COLON:: Type()
  | Type()
  ;

ArrayShapeKey
  : <T_CONSTANT_STRING>
  | <T_CONSTANT_INT>
  | Identifier()
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

$type = $parser->parse(new Source('list<non-empty-string>'));
```

This is the grammar to copy when a docblock has to be understood rather than
matched with a regular expression - by a static analyser, a serializer, a
mapper or an IDE plugin. The
[grammars repository](https://github.com/phplrt/grammars) also carries the
PSR-5 and TypeLang readings of the same idea, which are smaller and stricter.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
