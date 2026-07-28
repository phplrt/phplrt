# Grammar Syntax

A grammar file describes a language: the words it is made of, and the order
they may appear in. Here is one in full:

```pp2
// The words
%skip  T_WHITESPACE  \s++
%token T_DIGIT       \d++
%token T_PLUS        \+

// Where to start
%pragma root Sum

// The sentences
Sum : <T_DIGIT> (::T_PLUS:: <T_DIGIT>)* ;
```

Save it as `grammar.pp3` and it is ready to use:

```php
$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();
```

The syntax is a close relative of
[EBNF](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form), so
if you have written a grammar before, most of this will look familiar.

## Comments

C-style, both kinds:

```pp2
// Everything to the end of the line

/*
   Everything between the markers
 */
```

## Declaring Tokens

```pp2
%token T_DIGIT  \d++
```

A name and a regular expression, separated by whitespace. The name is
whatever you like; by convention tokens are `SCREAMING_CASE` with a `T_`
prefix, which makes them obvious in a rule.

`%skip` declares a token the parser will never see. Use it for whitespace
and comments — they still get recognized, so offsets stay correct, but they
do not clutter the grammar:

```pp2
%skip T_WHITESPACE  \s++
%skip T_COMMENT     //[^\n]*+
```

**Order matters.** The lexer takes the first pattern that matches, not the
longest one:

```pp2
%token T_STAR  \*      // matches first...
%token T_POW   \*\*    // ...so this never matches
```

Put the longer one first:

```pp2
%token T_POW   \*\*    // ✔
%token T_STAR  \*
```

Same story with keywords: declare `if` before your identifier pattern, or
`if` will be read as an identifier.

**A pattern cannot contain a literal space** — whitespace is what separates
the parts of the declaration. Write it as `\x20` or `\s`:

```pp2
%token T_TEXT  [a-z ]++     // ✘ breaks
%token T_TEXT  [a-z\x20]++  // ✔
%token T_TEXT  [a-z\s]++    // ✔
```

## Declaring Rules

A rule is a name, a separator, a body, and an optional semicolon:

```pp2
Sum : <T_DIGIT> ::T_PLUS:: <T_DIGIT> ;
```

The separator can be `:`, `=` or `::=` — they are identical, and the choice
exists only so grammars from other tools can be pasted in. Pick one and stay
with it.

By convention rules are `PascalCase`, which tells them apart from tokens at a
glance.

Long rules read better spread out:

```pp2
Expression
  : Term() ((<T_PLUS> | <T_MINUS>) Term())*
  ;
```

## What Goes In A Rule Body

### Tokens

Two spellings, and the difference is whether the token ends up in the result:

```pp2
Rule : <T_DIGIT> ;    // read it and keep it
Rule : ::T_COMMA:: ;  // read it and throw it away
```

Keep the things that carry information (names, numbers, literals). Discard the
punctuation that only holds the syntax together (commas, brackets, keywords).

```pp2
// A parenthesized expression: the brackets are required, but useless
Group : ::T_PARENTHESIS_OPEN:: Expression() ::T_PARENTHESIS_CLOSE:: ;
```

### Other Rules

Parentheses after the name — that is what tells a rule reference from a token:

```pp2
Sum : Number() ::T_PLUS:: Number() ;
```

The rule may be declared anywhere, including in a file that has not been read
yet. References are resolved after everything is loaded.

### Inline Patterns

A string literal declares a token right there, without naming it:

```pp2
Phone : <T_DIGIT>{3} "\-" <T_DIGIT>{4} ;
```

The same string written in several rules reuses one token, and such a token is
always discarded — it is punctuation by definition.

**It is a regular expression, not a literal string**, so it needs the same
escaping as `%token`:

```pp2
Rule : "\+" ;   // a plus sign
Rule : "+" ;    // ✘ a broken regex
```

Handy for one-off punctuation; for anything that appears more than twice,
declare a real token so the error messages can name it.

### Choice

```pp2
Primary : Number() | Name() | Group() ;
```

The alternatives are tried **in order**, and the first match wins. Nothing
else is tried, even if it would have matched more:

```pp2
Rule : "a" | "ab" ;   // ✘ never reads "ab"
Rule : "ab" | "a" ;   // ✔
```

### Grouping

```pp2
Rule : <T_A> (<T_B> | <T_C>) <T_D> ;
```

### Quantifiers

Any token, rule or group can be followed by one:

| Written    | Means                     |
|------------|---------------------------|
| `e?`       | zero or one time          |
| `e*`       | zero or more times        |
| `e+`       | one or more times         |
| `e{3}`     | exactly three times       |
| `e{2,5}`   | between two and five      |
| `e{2,}`    | two or more               |
| `e{,5}`    | up to five                |

```pp2
Arguments : Argument() (::T_COMMA:: Argument())* ;
Digits    : <T_DIGIT>{3} ;
Modifiers : Modifier()* ;
```

## Where Parsing Starts

By default it starts at the first rule in the file. Say otherwise with
`%pragma root`:

```pp2
%pragma root Expression
```

Worth setting explicitly once a grammar is split across files — the "first
rule" then depends on include order, which is a fragile thing to depend on.

`root` is currently the only pragma; anything else is an error.

## Including Other Files

```pp2
%include grammar/lexemes
%include grammar/expressions.pp3
```

- the path is relative to **the file the include is written in**;
- the extension may be omitted;
- a file included from several places is read **once**, so a shared
  `lexemes.pp3` can be included by everything that needs it.

Declarations land exactly where the `%include` is written, which matters for
tokens: an included token list appears at that point in the token order.

## Building A Result

A grammar with no reducers returns the tokens it kept. To build something
else, attach PHP:

```pp2
Number -> { return (int) $children->value; }
  : <T_DIGIT>
  ;
```

or a class, whose constructor receives the context and the children:

```pp2
Number -> \App\Ast\NumberNode
  : <T_DIGIT>
  ;
```

This has a page of its own: [PHP in a Grammar](/docs/compiler/code).

## The `#` Marker

A rule name may be prefixed with `#`:

```pp2
#Sum : <T_DIGIT> ::T_PLUS:: <T_DIGIT> ;
```

It comes from earlier versions of phplrt, where names survived compilation:
the marker kept the rule's name on the compiled parser so that a grammar
could be **modified after it was built** — the parser doubled as a
combinator, and an extension could reach a rule by name and replace or wrap
it at runtime.

Version 4.x compiles a grammar down to a flat table of numbered rules. Names
are a build-time thing now, kept only where a reducer needs one for its
generated method, so there is nothing left for the marker to address.

It is still accepted, and it now means "give this rule a reducer that hands
its children through" — which keeps the rule from being folded into its
parent by the optimizer. If you care about the result, write the reducer
you actually want:

```pp2
Sum -> { return new \App\Ast\SumNode($children); }
  : <T_DIGIT> ::T_PLUS:: <T_DIGIT>
  ;
```

## Naming Conventions

Nothing is enforced, but the usual style makes grammars much easier to read:

```pp2
%token T_NUMBER  \d++    // tokens: T_SCREAMING_CASE
Expression : ... ;       // rules:  PascalCase
```

## A Fuller Example

```pp2
%skip  T_WHITESPACE  \s++
%skip  T_COMMENT     //[^\n]*+

%token T_NUMBER      \d++(?:\.\d++)?
%token T_STRING      "[^"]*+"
%token T_TRUE        true
%token T_FALSE       false
%token T_NULL        null
%token T_NAME        [a-zA-Z_][a-zA-Z0-9_]*+

%pragma root Config

// name = value
// name = value
Config : Pair()* ;

Pair : <T_NAME> ::T_EQ:: Value() ;

Value
  : <T_NUMBER>
  | <T_STRING>
  | <T_TRUE>
  | <T_FALSE>
  | <T_NULL>
  | List()
  ;

// [a, b, c]
List
  : ::T_BRACKET_OPEN::
      (Value() (::T_COMMA:: Value())*)?
    ::T_BRACKET_CLOSE::
  ;

%token T_EQ             =
%token T_COMMA          ,
%token T_BRACKET_OPEN   \[
%token T_BRACKET_CLOSE  \]
```

Note `T_TRUE` before `T_NAME` — otherwise `true` is read as a name. And note
that tokens may be declared after the rules that use them; only the order of
the tokens *relative to each other* matters.
