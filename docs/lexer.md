# Lexer

> This package can be installed separately with `composer require phplrt/lexer`
>
> Describing a lexer in PHP additionally needs
> `composer require phplrt/lexer-builder`

The lexer is the first half of reading source code: it turns a stream of
characters into a stream of **tokens**. `23 + 42` becomes "a number, a plus,
a number" — and the parser never has to look at a single character again.

## A First Lexer

Describe the tokens, build the lexer, run it:

```php
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Source\Source;

$builder = new LexerBuilder();
$builder->addPattern('\d++', 'T_DIGIT');
$builder->addValue('+', 'T_PLUS');
$builder->addPattern('\s++', 'T_WHITESPACE');

$lexer = $builder->build()
    ->toLexer();

foreach ($lexer->lex(new Source('23 + 42')) as $token) {
    echo $token, "\n";
}
```

```
"23" (T_DIGIT)
" " (T_WHITESPACE)
"+" (T_PLUS)
" " (T_WHITESPACE)
"42" (T_DIGIT)
end of input
```

Two ways to describe a token:

- `addPattern('\d++')` — a **regular expression**;
- `addValue('+')` — a **literal string**, escaped for you. `addValue('+')` and
  `addPattern('\+')` are the same thing, but the first one is harder to get
  wrong.

The last token is always `end of input`. It is how the parser knows the
source has been read to the end.

## Order Matters

The lexer tries the patterns from top to bottom and takes the **first** one
that matches — not the longest. So this does not do what it looks like:

```php
$builder->addValue('*', 'T_STAR');
$builder->addValue('**', 'T_POW');   // never matched!
```

`**` is read as two `T_STAR` tokens, because `T_STAR` was declared first.
Put the longer literal above the shorter one:

```php
$builder->addValue('**', 'T_POW');   // ✔
$builder->addValue('*', 'T_STAR');
```

The same applies to keywords and identifiers — declare `if` before
`[a-z]+`, or `if` will always be read as an identifier.

## Hiding Whitespace

You almost never want whitespace in a grammar. Mark it as **hidden** and it
still gets recognized (so the offsets stay right) but the parser will not
see it:

```php
$builder->addPattern('\s++')
    ->hide();
$builder->addPattern('//[^\n]*+')
    ->hide(); // line comments too
```

Note that the token above has no name. A hidden token is not referred to by
anything, so naming it is optional.

`hide()` is shorthand for putting the token on the `Hidden` channel — see
[Tokens and Channels](/docs/lexer/tokens) for the general case.

## Regex Modifiers

By default, patterns are compiled with `S`, `u`, `s` and `m`. Add or remove
modifiers for the whole lexer:

```php
use Phplrt\Lexer\Builder\Definition\RegexModifier;

$builder->enable(RegexModifier::Caseless);  // /i
$builder->disable(RegexModifier::Utf8);     // no /u

$builder->addValue('true', 'T_TRUE');
// now matches "true", "TRUE" and "True"
```

## What You Get Back

`lex()` returns a list of tokens. Every token knows what it is, what it says
and where it was:

```php
foreach ($lexer->lex(new Source('23 + 42')) as $token) {
    $token->id;      // int    — 0, the position of its definition
    $token->name;    // string — "T_DIGIT"
    $token->value;   // string — "23"
    $token->offset;  // int    — 0, in bytes
    $token->size;    // int    — 2, in bytes
    $token->channel; // Channel::Default
}
```

You can also start somewhere other than the beginning:

```php
$lexer->lex(new Source('12 34'), offset: 3);
// only "34" and the end of input
```

More on this in [Tokens and Channels](/docs/lexer/tokens).

## Unrecognized Input

The lexer never fails on text it does not recognize. It emits a token on the
`Unknown` channel instead and carries on:

```php
$builder = new LexerBuilder();
$builder->addPattern('\d++', 'T_DIGIT');
$builder->addPattern('\s++')
    ->hide();
    
$lexer = $builder->build()
    ->toLexer();

foreach ($lexer->lex(new Source('12 @ 34')) as $token) {
    echo $token . "\n";
}

// "12" (T_DIGIT)
// "@" (unknown token)
// "34" (T_DIGIT)
// end of input
```

This is deliberate: a lexer that stops at the first strange character can
only report one problem, while an editor or a linter usually wants to see
the whole file. The parser is the one that decides an unknown token is an
error, and it does so with a message pointing at the exact spot.

## Reusing The Result

`build()` gives you a `LexerBuilderResult` — the compiled description — and
`toLexer()` turns that into a runnable lexer:

```php
$result = $builder->build();

$result->pattern;  // the single regex the whole lexer compiles down to
$result->names;    // [0 => 'T_DIGIT', 1 => 'T_PLUS', ...]
$result->channels; // [2 => 'Hidden', ...]

$lexer = $result->toLexer();
```

Building is not free — it validates every pattern, drops unreachable tokens
and merges everything into one big regular expression. Do it once and keep
the lexer around, or better still,
[generate the code](/docs/compiler/generation) and skip building entirely.

## How The Pattern Is Built

All the token definitions compile into a single PCRE pattern, and which token
matched is recorded with
[`(*MARK:n)`](https://www.pcre.org/current/doc/html/pcre2pattern.html):

```
/\G(?|(?:(?:\d++)(*MARK:0))|(?:(?:\s++)(*MARK:1))|(?:(?:[^\s]++)(*MARK:2)))/Ssum
```

One pass over the input, one regex, no per-token loop — this is where the
lexer's speed comes from. The last branch is the catch-all that produces
`Unknown` tokens.

`MarkersRegexGenerator` does this, and it is swappable if you ever need a
different strategy:

```php
use Phplrt\Lexer\Builder\Analysis\RegexConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Regex\RegexGeneratorInterface;

final class MyRegexGenerator implements RegexGeneratorInterface
{
    public function generate(array $tokens, array $flags): string
    {
        // $tokens is a map of token id => TokenDefinition
    }
}

$builder->addAnalysisPass(
    new RegexConstructionLexerAnalysisPass(new MyRegexGenerator()),
);
```

## Writing It In A Grammar Instead

Everything above has a shorter spelling in a `.pp3` file:

```pp2
%token T_DIGIT       \d++
%token T_PLUS        \+
%skip  T_WHITESPACE  \s++
```

A token nothing refers to by name does not have to be declared at all — a rule
declares it where it reads it, and the two spellings are the two methods above:

```pp2
Sum  : <T_DIGIT> "+" <T_DIGIT> ;          // addValue('+')
Expr : <T_DIGIT> /and|or/ <T_DIGIT> ;     // addPattern('and|or')
```

The modifiers and the compiler passes are settings of the grammar there, so a
grammar carries the way it wants to be compiled:

```pp2
%pragma lexer.pcre.flag  Caseless
%pragma lexer.check      \App\Grammar\MyValidationPass
```

That is usually where you want to be — see [Compiler](/docs/compiler). The
builder API is for the cases where the token list is not known in advance:
generated from a config file, a database, a plugin system.

## Next

- [Tokens and Channels](/docs/lexer/tokens) — the token API, channels and
  captures.
- [Nested Lexers](/docs/lexer/embedding) — string interpolation, PHP inside
  HTML and other "a different language starts here" situations.
