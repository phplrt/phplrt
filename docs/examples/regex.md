# Regular Expressions

A grammar that reads PCRE - the notation the `%token` lines of every other
example here are written in:

```
(?<year>[0-9]{4})-(?P<month>[0-9]{2})-(?:[0-9]{2})
(?i)^\Afoo(?=bar)(?!baz)(?<=qux)end\z$
a?b??c?+d*e*?f*+g+h+?i++j{2}k{2,5}l{2,5}?m{2,}+
```

Almost all of the work happens in the lexer, and the parser that follows is
three rules long: an alternation of concatenations of quantified things.

That split is the lesson. A character class is one token -
`\[\^?\]?(?:\\.|\[:\^?[a-z]++:\]|[^\]])*+\]` - because inside `[...]` every
character stands for itself, `*` and `|` included; letting the parser see the
pieces would mean teaching it to un-see them. The same goes for `(?#...)`,
`(?<name>` and `(?(1)`, each of which ends at a character of its own and is
read whole.

And where two spellings begin alike, the longer one is declared first:
`\?\+` before `\?\?` before `\?`, and the `\(\?<=` of a lookbehind before the
`\(\?P?<` of a named group. The lexer takes the first pattern that matches,
not the longest, so the order *is* the rule.

## Grammar

```pp2
/**
 * -----------------------------------------------------------------------------
 *  PCRE
 * -----------------------------------------------------------------------------
 *
 * The pattern a Perl-compatible regular expression is written of, without the
 * delimiters and the modifiers around it.
 *
 * @see https://github.com/hoaproject/Regex/blob/master/Source/Grammar.pp
 */

%pragma root Expression

/**
 * -----------------------------------------------------------------------------
 *  Groups
 * -----------------------------------------------------------------------------
 *
 *  A group is told from another by what is written past its parenthesis, so
 *  the longer spelling is declared before the shorter one it begins with.
 *
 *  A comment, a name and a reference each end at a character of their own, so
 *  each is read whole rather than by switching the reading over to a lexer of
 *  its own.
 */

%token T_INTERNAL_OPTION        \(\?[\-+]?[imsxUXn]++\)

%token T_COMMENT_GROUP          \(\?#((?:\\.|[^)])*+)\)

%token T_LOOKAHEAD              \(\?=
%token T_NEGATIVE_LOOKAHEAD     \(\?!
%token T_LOOKBEHIND             \(\?<=
%token T_NEGATIVE_LOOKBEHIND    \(\?<!

%token T_NAMED_REFERENCE        \(\?\(<((?:\\.|[^>])++)>\)
%token T_RELATIVE_REFERENCE     \(\?\(([+\-][0-9]++)\)
%token T_ABSOLUTE_REFERENCE     \(\?\(([0-9]++)\)
// The assertion a condition is written on opens with a parenthesis of its own
%token T_ASSERTION_REFERENCE    \(\?(?=\()

%token T_NAMED_CAPTURING        \(\?P?<((?:\\.|[^>])++)>
%token T_NON_CAPTURING          \(\?:
%token T_NON_CAPTURING_RESET    \(\?\|
%token T_ATOMIC_GROUP           \(\?>
%token T_CAPTURING_OPEN         \(
%token T_CAPTURING_CLOSE        \)

/**
 * -----------------------------------------------------------------------------
 *  Character Class
 * -----------------------------------------------------------------------------
 *
 *  Every character of a class stands for itself, an operator and a quantifier
 *  included, so a class is read whole rather than by the tokens it is written
 *  of. A bracket written first closes nothing, and a backslash escapes the
 *  character past it.
 */

%token T_CHARACTER_CLASS        \[\^?\]?(?:\\.|\[:\^?[a-z]++:\]|[^\]])*+\]

/**
 * -----------------------------------------------------------------------------
 *  Quantifiers
 * -----------------------------------------------------------------------------
 */

%token T_ZERO_OR_ONE_POSSESSIVE   \?\+
%token T_ZERO_OR_ONE_LAZY         \?\?
%token T_ZERO_OR_ONE              \?
%token T_ZERO_OR_MORE_POSSESSIVE  \*\+
%token T_ZERO_OR_MORE_LAZY        \*\?
%token T_ZERO_OR_MORE             \*
%token T_ONE_OR_MORE_POSSESSIVE   \+\+
%token T_ONE_OR_MORE_LAZY         \+\?
%token T_ONE_OR_MORE              \+
%token T_EXACTLY_N                \{[0-9]++\}
%token T_N_TO_M_POSSESSIVE        \{[0-9]++,[0-9]++\}\+
%token T_N_TO_M_LAZY              \{[0-9]++,[0-9]++\}\?
%token T_N_TO_M                   \{[0-9]++,[0-9]++\}
%token T_N_OR_MORE_POSSESSIVE     \{[0-9]++,\}\+
%token T_N_OR_MORE_LAZY           \{[0-9]++,\}\?
%token T_N_OR_MORE                \{[0-9]++,\}

/**
 * -----------------------------------------------------------------------------
 *  Literals
 * -----------------------------------------------------------------------------
 *
 *  The last of them reads any character at all, so it is declared last.
 */

%token T_ALTERNATION            \|
%token T_CHARACTER              \\(?:[aefnrt]|c[\x00-\x7f])
%token T_DYNAMIC_CHARACTER      \\(?:[0-7]{3}|x[0-9a-fA-F]{2}|x\{[0-9a-fA-F]++\})
%token T_CHARACTER_TYPE         \\(?:[CdDhHNRsSvVwWX]|[pP]\{[^}]++\})
%token T_ANCHOR                 \\[bBAZzG]|\^|\$
%token T_MATCH_POINT_RESET      \\K
// A bracket opens a class and never stands for itself, so it is written "\["
%token T_LITERAL                \\.|[^\[]

/**
 * -----------------------------------------------------------------------------
 *  Expression
 * -----------------------------------------------------------------------------
 */

Expression
  : Alternation()
  ;

Alternation
  : Concatenation() (::T_ALTERNATION:: Concatenation())*
  ;

Concatenation
  : (InternalOptions() | Assertion() | Condition() | Quantification())+
  ;

// (?i), (?-x)
InternalOptions
  : <T_INTERNAL_OPTION>
  ;

// (?=...), (?<!...)
Assertion
  : ( <T_LOOKAHEAD>
    | <T_NEGATIVE_LOOKAHEAD>
    | <T_LOOKBEHIND>
    | <T_NEGATIVE_LOOKBEHIND>
    )
    Alternation() ::T_CAPTURING_CLOSE::
  ;

// (?(1)yes|no), (?(<name>)yes|no), (?(?=...)yes|no)
Condition
  : ( <T_NAMED_REFERENCE>
    | <T_RELATIVE_REFERENCE>
    | <T_ABSOLUTE_REFERENCE>
    | ::T_ASSERTION_REFERENCE:: Assertion()
    )
    Concatenation()?
    (::T_ALTERNATION:: Concatenation()?)?
    ::T_CAPTURING_CLOSE::
  ;

Quantification
  : (CharacterClass() | Simple()) Quantifier()?
  ;

Quantifier
  : <T_ZERO_OR_ONE_POSSESSIVE>
  | <T_ZERO_OR_ONE_LAZY>
  | <T_ZERO_OR_ONE>
  | <T_ZERO_OR_MORE_POSSESSIVE>
  | <T_ZERO_OR_MORE_LAZY>
  | <T_ZERO_OR_MORE>
  | <T_ONE_OR_MORE_POSSESSIVE>
  | <T_ONE_OR_MORE_LAZY>
  | <T_ONE_OR_MORE>
  | <T_EXACTLY_N>
  | <T_N_TO_M_POSSESSIVE>
  | <T_N_TO_M_LAZY>
  | <T_N_TO_M>
  | <T_N_OR_MORE_POSSESSIVE>
  | <T_N_OR_MORE_LAZY>
  | <T_N_OR_MORE>
  ;

// [a-z], [^0-9], [[:alpha:]]
CharacterClass
  : <T_CHARACTER_CLASS>
  ;

Simple
  : Group()
  | Literal()
  ;

Group
  : <T_COMMENT_GROUP>
  | ( <T_NAMED_CAPTURING>
    | <T_NON_CAPTURING>
    | <T_NON_CAPTURING_RESET>
    | <T_ATOMIC_GROUP>
    | ::T_CAPTURING_OPEN::
    )
    Alternation() ::T_CAPTURING_CLOSE::
  ;

Literal
  : <T_CHARACTER>
  | <T_DYNAMIC_CHARACTER>
  | <T_CHARACTER_TYPE>
  | <T_ANCHOR>
  | <T_MATCH_POINT_RESET>
  | <T_LITERAL>
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

$pattern = $parser->parse(new Source('(?<year>[0-9]{4})-[0-9]{2}'));
```

The pattern is read without the delimiters and the modifiers around it, so
strip the `/.../i` before handing it over. What comes back is enough to count
the groups in a pattern, to rename them, to explain a regular expression to a
human, or to generate a string that matches one.

> **25+ more grammars.** [phplrt/grammars](https://github.com/phplrt/grammars)
> collects ready to read grammars for real languages - JSON5, TSV, semantic
> versions, DQL, PHQL, JMS types, PSR-5 and Doctrine annotations, Symfony
> expressions, Go! AOP pointcuts, Praspel contracts and more - each with sample
> inputs and a test that keeps it honest.
