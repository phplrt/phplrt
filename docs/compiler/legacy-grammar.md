# Legacy Grammar Syntax

This page describes the `.pp2` format — the one phplrt 3.x read. It is still
read exactly as it was, so a grammar written years ago compiles today without
being touched.

```php
$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp2'))
    ->getParser();
```

The extension is what decides the format, so keeping the file named `.pp2` is
all it takes.

> For a new grammar, write [`.pp3`](/docs/compiler/grammar) instead. Notes
> throughout this page mark what did not survive into that format.

## Comments

C-style, both kinds:

```pp2
// Everything to the end of the line

/*
   Everything between the markers
 */
```

## Declaring Tokens

A name and a regular expression, separated by whitespace:

```pp2
%token T_DIGIT  \d++
%skip  T_WHITESPACE  \s++
```

`%skip` declares a token the parser never sees — whitespace and comments still
get recognized, so offsets stay correct, but they do not clutter the grammar.

**Order matters.** The lexer takes the first pattern that matches, not the
longest one, so a longer literal goes above a shorter one:

```pp2
%token T_POW   \*\*    // ✔
%token T_STAR  \*
```

**A pattern cannot contain a literal space** — whitespace separates the parts
of the declaration. Write `\x20` or `\s` instead.

### States

A token may name the state the reading continues in, and a token declared as
`state:NAME` belongs to that state's lexer:

```pp2
%token        T_QUOTE_OPEN  "       -> string
%token string:T_TEXT        [^"]++
%token string:T_QUOTE_CLOSE "       -> default
```

Reading is nested rather than flat: naming a state from the initial one hands
the reading over to it, and naming `default` from inside gives the control
back. Everything the inner lexer read is carried by the token that entered it —
see [Nested Lexers](/docs/lexer/embedding).

Because it is nested, a token cannot jump from one named state into another:

```
error[UnsupportedTransitionException]: A fragment read in the state "one"
cannot be continued by the state "two": only entering a state and leaving it
back can be expressed
```

> Naming the state a token lands in was removed in `.pp3`, where a token says
> what it does instead.

## Declaring Rules

A name, a separator, a body, and an optional semicolon:

```pp2
Sum : <T_DIGIT> ::T_PLUS:: <T_DIGIT> ;
```

Three separators mean exactly the same thing, and a grammar may mix them:

```pp2
Sum ::= <T_DIGIT> ;
Sum  :  <T_DIGIT> ;
Sum  =  <T_DIGIT> ;
```

> The `=` and `::=` separators were removed in `.pp3`.

### The `#` Marker

A rule may be written with a `#` in front of its name:

```pp2
Root  : Pair() <T_B> ;
#Pair : <T_A> <T_A> ;
```

The marker keeps the name of the rule on the **compiled parser**, so the rule
can still be pointed at once the parser has been built — which is what a
grammar being extended at runtime needs. Without it, a rule the optimizer folds
into the one above leaves no trace:

```php
$result = $compiler->build();

$result->parser->constants;
// with the marker:     ['Root' => 0, 'Pair' => 1]
// without the marker:  ['Root' => 0]
```

It does not change what parsing returns: a marked rule and a plain one produce
exactly the same tree.

> The `#` marker was removed in `.pp3`.

## Reducers

A reducer turns a matched rule into a value, and it is written after the name
of the rule, before the separator.

### A Block Of Code

```pp2
Number -> { return (int) $children->value; }
  : <T_DIGIT>
  ;
```

The variables a body may use are the same in both formats — see
[PHP in a Grammar](/docs/compiler/code).

### A Class Name

A reducer may also be written as the name of a class, which is then built with
the context and the children:

```pp2
Number -> \App\Ast\NumberNode
  : <T_DIGIT>
  ;
```

This is shorthand for exactly one thing:

```php
new \App\Ast\NumberNode($ctx, $children)
```

So the class has to have a constructor taking those two, in that order. The
name is not resolved while the grammar is read: a class that does not exist is
only noticed where the parser runs.

> The class reducer was removed in `.pp3` — write the block of code and build
> whatever you like inside it.

## What Goes In A Rule Body

### Tokens

Two spellings, and the difference is whether the token ends up in the result:

```pp2
Rule : <T_DIGIT> ;    // read it and keep it
Rule : ::T_COMMA:: ;  // read it and throw it away
```

### Other Rules

Parentheses after the name — that is what tells a rule reference from a token:

```pp2
Sum : Number() ::T_PLUS:: Number() ;
```

The rule may be declared anywhere, including in a file that has not been read
yet.

### Inline Patterns

A string literal declares a token right there, without naming it. It is a
regular expression rather than a literal string, and such a token is always
discarded:

```pp2
Phone : <T_DIGIT>{3} "\-" <T_DIGIT>{4} ;
```

> The quotes were given a different meaning in `.pp3`, where they spell the
> text to read as it is written and an expression is written between slashes
> instead.

### Choice

```pp2
Primary : Number() | Name() | Group() ;
```

The alternatives are tried **in order**, and the first match wins.

### Grouping

```pp2
Rule : <T_A> (<T_B> | <T_C>) <T_D> ;
```

### Quantifiers

| Written  | Means                |
|----------|----------------------|
| `e?`     | zero or one time     |
| `e*`     | zero or more times   |
| `e+`     | one or more times    |
| `e{3}`   | exactly three times  |
| `e{2,5}` | between two and five |
| `e{2,}`  | two or more          |
| `e{,5}`  | up to five           |

```pp2
Arguments : Argument() (::T_COMMA:: Argument())* ;
```

> This format has no [predicates](/docs/compiler/grammar).

## Where Parsing Starts

By default it starts at the first rule in the file. Say otherwise with
`%pragma root`:

```pp2
%pragma root Expression
```

That is the only setting this format has. Anything else is an error:

```
error[UnsupportedPragmaException]: Unrecognized pragma "lexer.pcre.flag"
```

Everything else — PCRE modifiers, compiler passes — is configured in PHP
through [the lexer builder](/docs/lexer) and
[the parser builder](/docs/parser/builder).

## Including Other Files

```pp2
%include grammar/lexemes
%include grammar/expressions.pp2
```

- the path is relative to **the file the include is written in**;
- the extension may be omitted;
- a file included from several places is read **once**.

Declarations land exactly where the `%include` is written, which matters for
tokens: an included token list appears at that point in the token order.

## A Fuller Example

```pp2
%skip  T_WHITESPACE  \s++
%skip  T_COMMENT     //[^\n]*+

%token T_NUMBER      \d++(?:\.\d++)?
%token T_STRING      "[^"]*+"
%token T_TRUE        true
%token T_FALSE       false
%token T_NAME        [a-zA-Z_][a-zA-Z0-9_]*+

%token T_EQ             =
%token T_COMMA          ,
%token T_BRACKET_OPEN   \[
%token T_BRACKET_CLOSE  \]

%pragma root Config

Config : Pair()* ;

Pair -> \App\Ast\Pair
  : <T_NAME> ::T_EQ:: Value()
  ;

Value
  : <T_NUMBER>
    | <T_STRING>
    | <T_TRUE>
    | <T_FALSE>
    | List()
  ;

List
  : ::T_BRACKET_OPEN::
        (Value() (::T_COMMA:: Value())*)?
      ::T_BRACKET_CLOSE::
  ;
```

Note `T_TRUE` before `T_NAME` — otherwise `true` is read as a name. And note
that tokens may be declared after the rules that use them; only the order of
the tokens *relative to each other* matters.
