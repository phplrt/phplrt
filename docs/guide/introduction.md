# Introduction

Phplrt (PHP Language Recognition Tool) is a set of libraries for reading
source code: your own configuration format, a template language, a query
syntax, a subset of PHP - anything with rules.

You describe the language once, and phplrt turns that description into two
things:

- a **lexer**, which cuts the text into tokens (`42`, `+`, `"hello"`),
- a **parser**, which checks that those tokens appear in a valid order and
  builds whatever result you want out of them.

## Why?

Reach for phplrt when you have text with a structure in it, and what you
actually need is that structure rather than a yes/no answer. In practice that
means:

- **A small language for your users.** Filter expressions in a search bar
  (`price < 100 and brand in ("acme", "globex")`), permission rules, pricing
  formulas, alert conditions. Logic that is far easier to write as one line
  of text than as a deeply nested PHP array.
- **A format you designed yourself.** Migrations, fixtures, schema or
  protocol definitions, a routing table. Anything where JSON and YAML would
  force your users to spell out the structure instead of the meaning.
- **A template engine.** Text with islands of code inside it, like
  `{{ user.name }}` or `<?= ... ?>`. One lexer reads the plain text and hands
  each island over to another one, which is exactly what nested lexers are
  for.
- **A type or annotation syntax.** Docblock types such as
  `array<int, Foo|null>`, attribute arguments, route patterns with
  placeholders.
- **A language somebody else designed.** An SQL dialect, an `.ini` or `.env`
  variant, an interface definition file, some in-house legacy format that has
  no package on Packagist.
- **Tooling on top of a real language.** Linters, code generators and static
  analysis need positions and a tree they can walk over, not a string.

If `explode()` or one regex already does the job, use those instead. Phplrt
starts paying for itself when the structure nests, when you have to tell the
user *where* exactly they went wrong, or when the grammar is going to keep
changing for the next year.

## The Shortest Possible Example

Here is a calculator that adds numbers. The grammar is several lines:

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new Source(<<<'PP3'
        %token T_DIGIT       \d++
        %token T_PLUS        \+
        %skip  T_WHITESPACE  \s++

        Sum -> { return \array_sum($children); }
          : Number() (::T_PLUS:: Number())*
          ;

        Number -> { return (int) $children->value; }
          : <T_DIGIT>
          ;
        PP3))
    ->getParser();

echo $parser->parse(new Source('2 + 3 + 4')); // 9
```

That is the whole library in miniature: `%token` describes the words,
the rules describe the sentences, and `-> { ... }` says what to build.

## The Two Halves

Phplrt is split into a **development** half and a **runtime** half, and it
is worth knowing which is which.

The **compiler** (`phplrt/compiler`) reads grammar files. It is a
[compiler-compiler](https://en.wikipedia.org/wiki/Compiler-compiler): it does
not read *your users'* code, it reads *your grammar* and produces a parser.
You use it while developing, and - ideally - you run it once and commit the
result.

The **runtime** (`phplrt/runtime`, i.e. the lexer, the parser and the source
reader) is what actually reads your users' code. It knows nothing about grammar files: it takes a compiled table
of tokens and rules and runs it.

```
grammar.pp3  ──[ compiler ]──▶  Parser.php  ──[ runtime ]──▶  your AST
   (dev)                        (committed)                   (production)
```

You can skip the middle step and compile the grammar on every run - the
example above does exactly that. It is convenient while you are still
changing the grammar every five minutes, and slow once you are not.

## Components

Each component is a separate composer package, and they talk to each other
through interfaces, so you can replace any of them with your own.

### Source

Whatever you are reading, phplrt wants it wrapped in a *source* object: a
file, a string, or a stream. That object knows how to give up its content and
what to call itself in an error message.

```bash
composer require phplrt/source
```

[Read more →](/docs/source)

### Lexer

The lexer turns characters into tokens. It is regex-driven, supports hidden
tokens (whitespace, comments) and can hand a fragment over to another lexer -
which is how you read a string literal, or PHP inside HTML.

```bash
composer require phplrt/lexer
```

[Read more →](/docs/lexer)

### Parser

The parser takes the tokens and matches them against a grammar. It recognizes
a [PEG](https://en.wikipedia.org/wiki/Parsing_expression_grammar) by
backtracking recursive descent over a table of rules, predicts with FIRST
sets so that hopeless branches are skipped by a single lookup, and builds the
result only once the whole input has been recognized. In practice: the
alternatives are ordered - the first one that matches wins - and there is
never any ambiguity.

```bash
composer require phplrt/parser
```

[Read more →](/docs/parser)

### Lexer Builder and Parser Builder

These describe a lexer and a grammar *in PHP*, then compile and optimize
them. The grammar compiler is built on top of them, and you can use them
directly if you would rather build your grammar in code than in a file.

```bash
composer require phplrt/lexer-builder phplrt/parser-builder
```

[Read more →](/docs/parser/builder)

### Compiler

The compiler reads `.pp3` grammar files, resolves `%include` references
between them, and either hands you a ready parser or writes one out as PHP
code.

```bash
composer require phplrt/compiler --dev
```

[Read more →](/docs/compiler)

### Exception

Errors that point at a piece of source code are much easier to fix than
errors that do not. This component renders them:

```
error[UnexpectedTokenException]: Syntax error, unexpected "+" (T_PLUS)
 --> example.txt:1:5
  |
1 | 2 + + 3
  |     ^
```

```bash
composer require phplrt/exception
```

[Read more →](/docs/errors)

## Where To Go Next

- [Quick Start](/docs/guide/quick-start) - build a small language end to end.
- [Grammar Syntax](/docs/compiler/grammar) - everything a `.pp3` file can say.
- [Lexer](/docs/lexer) - tokens, channels and nested lexers.
- [Parser](/docs/parser) - rules, reducers and the result they build.
