# Composer Constraints

The little language the `require` section of every `composer.json` is written
in:

```
^1.2.3
>=2.0 <3.0.0-beta
~1.2 || ^2.0 || 3.*
1.0 - 2.0
dev-main as 1.0.x-dev
```

Two things make this harder than it looks, and the lexer settles both.

A version is a single token rather than a rule. `1.0.0-beta.1+build` is full
of dots, dashes and pluses, and reading them separately would leave the parser
guessing which dash opens a range and which belongs to a pre-release. The one
dash that *does* open a range is told apart by what follows it - `-(?=\s)` -
which is the same rule Composer itself goes by.

The second is that a space means "and". `>=2.0 <3.0.0-beta` is one range built
of two comparisons, and `Comparison()+` says so without an operator to read.

## Grammar

```pp3
/**
 * -----------------------------------------------------------------------------
 *  Composer Version Constraint
 * -----------------------------------------------------------------------------
 *
 * The versions a package is allowed to be installed at, written in the
 * "require" section of a "composer.json".
 *
 * @see https://getcomposer.org/doc/articles/versions.md
 */

%pragma root Constraint

%skip  T_WHITESPACE        \s++

%token T_OR                \|\||\|
%token T_COMMA             ,

%token T_CARET             \^
%token T_TILDE             ~
%token T_GTE               >=
%token T_LTE               <=
%token T_NEQ               !=
%token T_GT                >
%token T_LT                <
%token T_EQ                ==|=

%token T_AS                (?i)as\b
%token T_HYPHEN            -(?=\s)

// 1.2.3, v1.2, 1.*, 1.2.x, dev-main, master, 1.0.0-beta.1+build
%token T_VERSION           v?[0-9]++(?:\.(?:[0-9]++|[xX*]))*+(?:[.\-+][a-zA-Z0-9][a-zA-Z0-9.\-+]*+)?|[*]|[a-zA-Z][a-zA-Z0-9._\-/]*+

Constraint
  : Range() ((::T_OR:: | ::T_COMMA::) Range())*
  ;

// A space between two constraints reads as "and"
Range
  : HyphenRange()
  | Comparison()+
  ;

// 1.0 - 2.0
HyphenRange
  : Version() ::T_HYPHEN:: Version()
  ;

Comparison
  : Operator()? Version()
  ;

Operator
  : <T_CARET>
  | <T_TILDE>
  | <T_GTE>
  | <T_LTE>
  | <T_NEQ>
  | <T_GT>
  | <T_LT>
  | <T_EQ>
  ;

// A branch aliased to a version: "dev-main as 1.0.x-dev"
Version
  : <T_VERSION> (::T_AS:: <T_VERSION>)?
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

$constraint = $parser->parse(new Source('~1.2 || ^2.0 || 3.*'));
```

The grammar recognises the constraint and gives back its parts; deciding
whether a given version satisfies it is a job for PHP, not for a grammar. Hang
a [reducer](/docs/parser/ast) on `Comparison` and `HyphenRange` and you have
the matcher.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
