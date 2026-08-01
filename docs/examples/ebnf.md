# EBNF

A grammar for writing grammars: the notation half the specifications on your
shelf are written in, read by a grammar of its own.

```ebnf
(* An arithmetic expression, written with all three brackets *)

<expression> ::= <term> { ("+" | "-") <term> }
<term>       ::= <factor> { ("*" | "/") <factor> }
<factor>     ::= [ "-" ] <primary>
<primary>    ::= <number> | <name> | "(" <expression> ")"
```

The interesting rule is `Element`, and the reason is the semicolon: EBNF ends
a rule with one, except when it does not. Without it, the name opening the
next rule looks exactly like a name used inside the current one, and a greedy
reading would swallow the whole file into the first rule.

The way out is a **predicate**. `!(Identifier() ::T_ASSIGN::)` matches nothing
and consumes nothing - it only asks whether what comes next is a name followed
by `::=`, and fails if it is. A name that opens a rule therefore cannot be
read as part of the rule before it. See
[Predicates](/docs/compiler/grammar) for what else they are good for.

## Grammar

```pp2
/**
 * -----------------------------------------------------------------------------
 *  EBNF
 * -----------------------------------------------------------------------------
 *
 * Backus-Naur form with the three brackets that make a repetition and an
 * option writable without a rule of their own.
 *
 * @see https://www.iso.org/standard/26153.html
 */

%pragma root RuleList

%skip  T_WHITESPACE            \s++
%skip  T_COMMENT               \(\*.*?\*\)

%token T_ASSIGN                ::=|=
%token T_ANGLE_OPEN            <
%token T_ANGLE_CLOSE           >
%token T_BRACKET_OPEN          \[
%token T_BRACKET_CLOSE         \]
%token T_BRACE_OPEN            \{
%token T_BRACE_CLOSE           \}
%token T_PARENTHESIS_OPEN      \(
%token T_PARENTHESIS_CLOSE     \)
%token T_OR                    \|
%token T_SEMICOLON             ;
%token T_COMMA                 ,

%token T_STRING                "[^"]*+"|'[^']*+'
%token T_NAME                  [a-zA-Z][a-zA-Z0-9_-]*+

RuleList
  : Rule()*
  ;

// <name> ::= alternatives
Rule
  : Identifier() ::T_ASSIGN:: Alternatives() ::T_SEMICOLON::?
  ;

Alternatives
  : Alternative() (::T_OR:: Alternative())*
  ;

Alternative
  : Element()*
  ;

/**
 * A rule ends where the next one begins, and the semicolon closing it is
 * optional, so a name followed by the separator opens a rule rather than
 * belonging to the one before it.
 */
Element
  : Optional()
  | Repetition()
  | Group()
  | Terminal()
  | !(Identifier() ::T_ASSIGN::) Identifier()
  ;

// [ ... ] - written none or one time
Optional
  : ::T_BRACKET_OPEN:: Alternatives() ::T_BRACKET_CLOSE:: ::T_COMMA::?
  ;

// { ... } - written none or more times
Repetition
  : ::T_BRACE_OPEN:: Alternatives() ::T_BRACE_CLOSE:: ::T_COMMA::?
  ;

// ( ... ) - read as one
Group
  : ::T_PARENTHESIS_OPEN:: Alternatives() ::T_PARENTHESIS_CLOSE:: ::T_COMMA::?
  ;

// <name> or name
Identifier
  : ::T_ANGLE_OPEN:: <T_NAME> ::T_ANGLE_CLOSE:: ::T_COMMA::?
  | <T_NAME> ::T_COMMA::?
  ;

Terminal
  : <T_STRING> ::T_COMMA::?
  ;
```

## Usage

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

$rules = $parser->parse(new File(__DIR__ . '/expression.ebnf'));
```

Both spellings of everything are accepted on purpose: `::=` and `=` for the
separator, `<name>` and `name` for a reference, and the commas ISO 14977
insists on between elements are read and dropped wherever they turn up.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
