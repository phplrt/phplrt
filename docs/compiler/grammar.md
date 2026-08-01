# Grammar Syntax

A grammar file describes a language: the words it is made of, and the order
they may appear in. Here is one in full:

```pp3
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

```pp3
// Everything to the end of the line

/*
   Everything between the markers
 */
```

## Declaring Tokens

```pp3
%token T_DIGIT  \d++
```

A name and a regular expression, separated by whitespace. The name is
whatever you like; by convention tokens are `SCREAMING_CASE` with a `T_`
prefix, which makes them obvious in a rule.

`%skip` declares a token the parser will never see. Use it for whitespace
and comments - they still get recognized, so offsets stay correct, but they
do not clutter the grammar:

```pp3
%skip T_WHITESPACE  \s++
%skip T_COMMENT     //[^\n]*+
```

**Order matters.** The lexer takes the first pattern that matches, not the
longest one:

```pp3
%token T_STAR  \*      // matches first...
%token T_POW   \*\*    // ...so this never matches
```

Put the longer one first:

```pp3
%token T_POW   \*\*    // ✔
%token T_STAR  \*
```

Same story with keywords: declare `if` before your identifier pattern, or
`if` will be read as an identifier.

**A declaration is one line.** It is read by a lexer of its own, which starts
at `%token` and stops at the line break, and it expects exactly three things:
a name, the expression recognizing the token, and - optionally - an arrow with
what the token [does](#token-actions).

```
%token  string:T_QUOTE  "  -> state(strings), channel(quotes)
        ▲      ▲        ▲     ▲
        state  name     expr  actions
```

**A pattern cannot contain a literal space** - whitespace is what separates
the parts of the declaration. Write it as `\x20` or `\s`:

```pp3
%token T_TEXT  [a-z ]++     // ✘ breaks
%token T_TEXT  [a-z\x20]++  // ✔
%token T_TEXT  [a-z\s]++    // ✔
```

Anything else on the line is an error, which is how a `.pp2` habit gets
noticed:

```
error[UnexpectedTokenException]: Syntax error, unexpected "->" (T_PATTERN)
 --> /app/grammar.pp3:1:20
  |
1 | %token T_QUOTE  "  -> string
  |                    ^^
```

## Token Actions

A declaration may end with `->` and say what the token *does* besides being
read. There are three actions, and each is written as a call:

| Action       | What the token does                                       |
|--------------|-----------------------------------------------------------|
| `channel(x)` | Emits the token to the channel `x`                        |
| `state(x)`   | Hands the reading over to the lexer of the state `x`      |
| `exit()`     | Gives the control back to the lexer that entered this one |

### channel(x)

A [channel](/docs/lexer/tokens) keeps a token out of the grammar without
throwing it away - documentation comments are the usual reason:

```pp3
%token T_DOC_COMMENT  /\*\*.*?\*/  -> channel(docblocks)
```

The parser never sees it, but it is right there in the token stream for
anything that wants it. `%skip` is shorthand for the built-in `Hidden`
channel, so these two lines mean the same thing:

```pp3
%skip  T_WHITESPACE  \s++
%token T_WHITESPACE  \s++  -> channel(Hidden)
```

### state(x) and exit()

A token may hand the reading over to a lexer of its own, which is how a
fragment written in different lexical rules is read - a string literal, a
comment, an embedded language:

```pp3
%token        T_QUOTE_OPEN  "       -> state(string)
%token string:T_TEXT        [^"]++
%token string:T_QUOTE_CLOSE "       -> exit()
```

A token declared as `state:NAME` belongs to that state's lexer. Everything
that lexer reads is carried by the token that entered it, so `T_TEXT` never
reaches the outer stream. See [Nested Lexers](/docs/lexer/embedding).

### Several At Once

Actions are separated by commas, and the order does not matter:

```pp3
%token T_QUOTE_OPEN  "  -> state(string), channel(strings)
```

A token is read once and therefore goes to exactly one place, so writing two
actions that both move the reading - `state(x), exit()` - is an error.

## Tokens Belonging To Every State

Whitespace and comments are usually the same wherever they appear, and
repeating them in every state is how a grammar drifts out of sync with itself.
Write `*:` instead of a state name and the token is added to all of them:

```pp3
%skip  *:T_WHITESPACE  \s++

%token T_QUOTE_OPEN  "  -> state(string)
%token string:T_TEXT  [^"]++
%token string:T_QUOTE_CLOSE  "  -> exit()
```

Both the initial state and `string` now skip whitespace.

Three things worth knowing:

- the token is added to **every** state, including ones declared later or in
  a file included afterwards - the states are all known only once the whole
  grammar has been read;
- inside a state it is tried **after** the tokens that state declares itself,
  so a state with a catch-all pattern still wins;
- a [lexer written by hand](#lexers-written-by-hand) is left alone: what it
  recognizes is decided by that lexer, not by a declaration.

## Lexers Written By Hand

Some fragments cannot be described by regular expressions at all - heredocs,
indentation-sensitive blocks, another language entirely. `%lexer` names a
state and gives the expression building the lexer that reads it:

```pp3
%token T_PHP_OPEN  <\?php  -> state(php)

%lexer php -> { new \App\Lexer\PhpTokenLexer() }
```

The body is an **expression**, not a block of statements - whatever it
evaluates to has to be a `LexerInterface`. Note there is no `return` and no
semicolon.

Such a lexer decides on its own where its fragment ends: it stops, and control
returns to the lexer that called it, so it needs no token doing `exit()`. See
[Nested Lexers](/docs/lexer/embedding).

## Declaring Rules

A rule is a name, a colon, a body, and an optional semicolon:

```pp3
Sum : <T_DIGIT> ::T_PLUS:: <T_DIGIT> ;
```

The colon is the only separator there is. By convention rules are
`PascalCase`, which tells them apart from tokens at a glance.

Long rules read better spread out:

```pp3
Expression
  : Term() ((<T_PLUS> | <T_MINUS>) Term())*
  ;
```

## What Goes In A Rule Body

### Tokens

Two spellings, and the difference is whether the token ends up in the result:

```pp3
Rule : <T_DIGIT> ;    // read it and keep it
Rule : ::T_COMMA:: ;  // read it and throw it away
```

Keep the things that carry information (names, numbers, literals). Discard the
punctuation that only holds the syntax together (commas, brackets, keywords).

```pp3
// A parenthesized expression: the brackets are required, but useless
Group : ::T_PARENTHESIS_OPEN:: Expression() ::T_PARENTHESIS_CLOSE:: ;
```

### Other Rules

Parentheses after the name - that is what tells a rule reference from a token:

```pp3
Sum : Number() ::T_PLUS:: Number() ;
```

The rule may be declared anywhere, including in a file that has not been read
yet. References are resolved after everything is loaded.

### Inline Tokens

A rule may declare a token right where it reads it, without naming it. There
are two spellings, and the difference is whether what you write is text or an
expression:

| Written | Means                                             |
|---------|---------------------------------------------------|
| `"..."` | the **text** to read, exactly as it is written     |
| `/.../` | the **regular expression** recognizing the token   |

```pp3
Sum  : <T_NUMBER> "+" <T_NUMBER> ;          // a plus sign
Expr : <T_NUMBER> /and|or|xor/ <T_NUMBER> ; // one of three words
```

Quotes are the one you want for punctuation: nothing inside them is special,
so `"+"`, `"("` and `"**"` mean exactly what they look like. Slashes are for
when you need a choice, a character class or a quantifier.

The same token written in several rules is declared **once**, and such a token
is always discarded - it is punctuation by definition:

```pp3
Sum     : <T_NUMBER> "+" <T_NUMBER> ;
Unary   : "+" <T_NUMBER> ;              // the very same token
```

Escaping is only ever needed for the delimiter itself - `\"` inside quotes,
`\/` inside slashes.

Handy for one-off punctuation; for anything that appears more than twice,
declare a real token so the error messages can name it.

> A slash also opens a comment, so `//` and `/*` are read as one. Write `/\//`
> for a lone slash.

### Choice

```pp3
Primary : Number() | Name() | Group() ;
```

The alternatives are tried **in order**, and the first match wins. Nothing
else is tried, even if it would have matched more:

```pp3
Rule : "a" | "ab" ;   // ✘ never reads "ab"
Rule : "ab" | "a" ;   // ✔
```

### Grouping

```pp3
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

```pp3
Arguments : Argument() (::T_COMMA:: Argument())* ;
Digits    : <T_DIGIT>{3} ;
Modifiers : Modifier()* ;
```

### Predicates

A predicate looks at what comes next **without reading it**. Nothing is
consumed and nothing lands in the tree - the only thing that happens is that
the rule either goes on or gives up.

| Written | Means                                     |
|---------|-------------------------------------------|
| `&e`    | go on only if `e` matches here            |
| `!e`    | go on only if `e` does **not** match here |

The classic use is refusing a position that belongs to somebody else. A name
that is not a function call:

```pp3
Variable : <T_NAME> !::T_PARENTHESIS_OPEN:: ;
```

`foo` matches. `foo(` does not - and, importantly, the `(` is still there
afterwards for whatever rule does want it.

The other direction is committing to a branch without reading it twice:

```pp3
// Only try the expensive rule when the line really starts with "fn"
Closure : &::T_FN:: FunctionLiteral() ;
```

A predicate is written **before** the quantifier, so it looks ahead at the
whole thing at once:

```pp3
Rule : &<T_DIGIT>+ Number() ;   // look ahead at one or more digits
```

Two things to keep in mind:

- a predicate contributes nothing to `$children`, so adding one does not shift
  the positions the reducer reads;
- it costs a real attempt at matching. `!Expression()` will parse a whole
  expression and throw it away, so prefer looking ahead at a token.

This is the one thing in a rule body that describes *how* something is read
rather than *what* the language contains, which is why EBNF has no equivalent.

## Where Parsing Starts

By default, it starts at the first rule in the file. Say otherwise with
`%pragma root`:

```pp3
%pragma root Expression
```

Worth setting explicitly once a grammar is split across files - the "first
rule" then depends on include order, which is a fragile thing to depend on.

## Settings

`%pragma` configures the compilation from the grammar itself, so a grammar
that needs a particular setting carries it instead of relying on the code that
compiles it.

| Setting                   | What it does                                       |
|---------------------------|----------------------------------------------------|
| `root <Rule>`             | Where parsing starts                               |
| `lexer.pcre.flag <M>`     | Compiles the lexer's pattern with a PCRE modifier  |
| `lexer.pcre.disable <M>`  | Compiles it without one                            |
| `lexer.pass <Class>`      | Registers a lexer pass, normalizing                |
| `lexer.check <Class>`     | Registers a lexer pass, checking                   |
| `lexer.optimize <Class>`  | Registers a lexer pass, optimizing                 |
| `lexer.complete <Class>`  | Registers a lexer pass, checking after optimizing  |
| `lexer.disable <Class>`   | Drops a lexer pass, whenever it was registered     |
| `parser.pass <Class>`     | Registers a parser pass, normalizing               |
| `parser.check <Class>`    | Registers a parser pass, checking                  |
| `parser.optimize <Class>` | Registers a parser pass, optimizing                |
| `parser.complete <Class>` | Registers a parser pass, checking after optimizing |
| `parser.disable <Class>`  | Drops a parser pass                                |

Anything else is an error.

### PCRE Modifiers

The lexer compiles its tokens into one pattern, and these say which modifiers
that pattern carries. A modifier is named either the way PCRE spells it or the
way phplrt calls it:

```pp3
%pragma lexer.pcre.flag     Caseless   // ...or "i"
%pragma lexer.pcre.disable  Utf8       // ...or "u"
```

By default, the pattern is compiled with `S`, `u`, `s` and `m`. See
[RegexModifier](/docs/lexer) for what each of them means.

### Compiler Passes

A pass rewrites or checks the lexer or the grammar while it is being built,
and the setting is named after the moment it runs at:

```pp3
%pragma parser.check     \App\Grammar\NoLeftFactoringPass
%pragma lexer.optimize   \App\Grammar\MergeKeywordsPass
```

The class is created with no arguments and must implement
`LexerCompilerPassInterface` or `ParserCompilerPassInterface` - a class that
does not exist, or implements the wrong one, is reported at the line it is
written on.

A built-in pass can be dropped by name, which is how a grammar opts out of an
optimization it does not want:

```pp3
%pragma parser.disable \Phplrt\Parser\Builder\Compiler\NestedConcatenationParserCompilerPass
```

[Building a Grammar](/docs/parser/builder) describes what the passes are and
when each priority runs.

## Including Other Files

```pp3
%include grammar/lexemes
%include grammar/expressions.pp3
```

- the path is relative to **the file to include is written in**;
- the extension may be omitted;
- a file included from several places is read **once**, so a shared
  `lexemes.pp3` can be included by everything that needs it.

Declarations land exactly where the `%include` is written, which matters for
tokens: an included token list appears at that point in the token order.

## Building A Result

A grammar with no reducers returns the tokens it kept. To build something
else, attach PHP:

```pp3
Number -> { return (int) $children->value; }
  : <T_DIGIT>
  ;
```

A block of code is the only form a reducer takes - build the node inside it:

```pp3
Number -> { return new \App\Ast\NumberNode($offset, $children->value); }
  : <T_DIGIT>
  ;
```

This has a page of its own: [PHP in a Grammar](/docs/compiler/code).

## Naming Conventions

Nothing is enforced, but the usual style makes grammars much easier to read:

```pp3
%token T_NUMBER  \d++    // tokens: T_SCREAMING_CASE
Expression : ... ;       // rules:  PascalCase
```

## A Fuller Example

```pp3
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

Pair : <T_NAME> "=" Value() ;

Value
  : <T_NUMBER>
  | <T_STRING>
  | <T_TRUE>
  | <T_FALSE>
  | <T_NULL>
  | List()
  ;

// [a, b, c]
List : "[" (Value() ("," Value())*)? "]" ;
```

Note `T_TRUE` before `T_NAME` - otherwise `true` is read as a name. And note
that the punctuation is never declared: `"="`, `"["`, `","` and `"]"` each
declare their token where they are read, and none of them needs escaping
because a value is not an expression.
