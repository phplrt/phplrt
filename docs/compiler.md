# Compiler

> This package is for development only:
> `composer require phplrt/compiler --dev`

The compiler reads a grammar file - the tokens, the rules, the reducers -
and gives you back a working parser. It is the friendly front end to
everything the [lexer builder](/docs/lexer) and
[parser builder](/docs/parser/builder) can do.

## Reading A Grammar

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

echo $parser->parse(new Source('2 + 2'));
```

`load()` reads the grammar (and everything it `%include`s), and `getParser()`
compiles it. That is the whole thing for a script or a prototype.

You can load several grammars into one compiler - they all end up in the same
lexer and parser:

```php
$compiler = new Compiler();
$compiler->load(new File(__DIR__ . '/lexemes.pp3'));
$compiler->load(new File(__DIR__ . '/expressions.pp3'));

$parser = $compiler->getParser();
```

## Generating Code

Reading a grammar takes real time, and the grammar does not change between
requests. So: do it once, write the result to a file, and commit the file.

```php
new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->generate()
        ->withNamespaceName('App\Language')
        ->withClassName('LanguageParser')
        ->save(__DIR__ . '/Parser.php');
```

Now production never sees the compiler at all:

```php
$parser = new App\Language\LanguageParser();
```

[Code Generation](/docs/compiler/generation) covers this in full.

## Grammar Formats

The format is decided by the file extension:

| Extension | Format                                                                                    |
|-----------|-------------------------------------------------------------------------------------------|
| `.pp`     | The legacy [Hoa](https://github.com/hoaproject/Compiler) format - **no longer supported** |
| `.pp2`    | The older format, described in [Legacy Grammar](/docs/compiler/legacy-grammar)            |
| `.pp3`    | The current format, described in [Grammar](/docs/compiler/grammar)                        |

Write `.pp3` for anything new. A `.pp2` file keeps being read the way it always
was, so an existing grammar needs no attention.

A grammar that did not come from a file (a `Source`, a string) is read as the
newest format, since there is no extension to go by:

```php
$compiler->load(new Source('%token T_DIGIT \d++  Num : <T_DIGIT> ;'));
```

Reading a `.pp` file gives you a clear error rather than a confusing one:

```
error[UnsupportedFormatException]: Grammar files written in the "pp" format
are not supported
 --> /app/grammar.pp:1:1
```

## Splitting A Grammar Up

Real grammars get long. `%include` pulls in another file, and the
declarations land exactly where to include is written:

```pp3
%include grammar/lexemes
%include grammar/literals
%include grammar/expressions

%pragma root Expression
```

A few useful details:

- the path is **relative to the file to include is written in**;
- the extension may be omitted - every known format is tried in turn;
- a grammar reached from several places is read **once**, so a shared
  `lexemes.pp3` can be included by every file that needs it.

If the file is missing, the error names both the include and the file that
wanted it:

```
error[GrammarNotFoundException]: grammar/missing: failed to open stream:
No such file or directory
 --> /app/grammar.pp3:1:1
  |
1 | %include grammar/missing
  | ^^^^^^^^^^^^^^^^^^^^^^^^
```

Errors inside an included grammar are reported the same way, with the chain
of includes that led there.

## Getting At The Pieces

The compiler is a thin layer over the two builders, and both are public:

```php
$compiler = new Compiler();
$compiler->load(new File(__DIR__ . '/grammar.pp3'));

// Add a token the grammar file does not mention
$compiler->lexer->addPattern('#[^\n]*+')
    ->hide();

// Add a compiler pass of your own
$compiler->parser->addCompilerPass(new MyValidationPass());

$parser = $compiler->getParser();
```

`build()` gives you the compiled description instead of a ready parser -
which is what the generator works from:

```php
$result = $compiler->build();

$result->lexer;  // LexerBuilderResult
$result->parser; // ParserBuilderResult
```

## Errors

Everything that can go wrong points at the exact spot in the grammar:

```
error[UnsupportedPragmaException]: Unrecognized pragma "unknown"
 --> /app/grammar.pp3:2:1
  |
1 | %token T_A a
2 | %pragma unknown value
  | ^^^^^^^^^^^^^^^^^^^^^
3 | A : <T_A> ;
```

The kinds you are likely to meet:

| Exception                        | Cause                                                                                   |
|----------------------------------|-----------------------------------------------------------------------------------------|
| `UnexpectedTokenException`       | The grammar file itself is malformed                                                    |
| `GrammarNotFoundException`       | `%include` points at a file that is not there                                           |
| `UnsupportedPragmaException`     | An unknown `%pragma`                                                                    |
| `UnsupportedFormatException`     | A legacy `.pp` file, or an unknown extension                                            |
| `UnsupportedTransitionException` | A token switching between two named states                                              |
| `CompilationFailedException`     | The grammar is well-formed but wrong: left recursion, an undefined rule, a broken regex |

## Next

- [Grammar Syntax](/docs/compiler/grammar) - everything a `.pp3` file can say.
- [Legacy Grammar Syntax](/docs/compiler/legacy-grammar) - everything a `.pp2`
  file can say.
- [PHP in a Grammar](/docs/compiler/code) - reducers and the variables they get.
- [Code Generation](/docs/compiler/generation) - namespaces, class names, and
  what the output looks like.
