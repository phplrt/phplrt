# Nested Lexers

Some fragments of a language are not written in that language. Inside a
string literal, `+` is just a plus sign. Inside a comment, nothing means
anything at all. Inside `<?php ... ?>`, a whole other language starts.

A single list of regular expressions cannot express that, so phplrt lets one
lexer hand the reading over to another.

## The Idea

A token can **enter** another lexer. From that point on, the other lexer
reads — with its own tokens, its own rules — until one of *its* tokens
**exits**. Everything it read is carried by the token that entered it.

```php
use Phplrt\Lexer\Builder\LexerBuilder;

$builder = new LexerBuilder();
$builder->addPattern('\d++', 'T_DIGIT');
$builder->addValue('"', 'T_QUOTE_OPEN')
    ->enter('string'); // <- hand over
$builder->addPattern('\s++')
    ->hide();

// A lexer that only knows about the insides of a string
$string = $builder->addLexer('string');
$string->addPattern('\\\\.', 'T_ESCAPE');
$string->addPattern('[^"\\\\]++', 'T_TEXT');
$string->addValue('"', 'T_QUOTE_CLOSE')
    ->exit();          // <- hand back
```

Reading `1 "he\"llo" 2` gives:

```
T_DIGIT        "1"
T_QUOTE_OPEN   """          <- carries the whole string
    T_TEXT         "he"
    T_ESCAPE       "\""
    T_TEXT         "llo"
    T_QUOTE_CLOSE  """
T_DIGIT        "2"
end of input
```

Note what did *not* happen: `T_DIGIT` did not match the `llo`, and the
escaped quote did not end the string. The inner lexer knows nothing about
digits, and the outer one never got a chance to look.

## TokenEmbedding

A token that entered another lexer is a `TokenEmbedding` — a normal token
with children:

```php
use Phplrt\Lexer\Token\TokenEmbedding;

foreach ($lexer->lex($source) as $token) {
    if (!$token instanceof TokenEmbedding) {
        continue;
    }

    foreach ($token->children as $child) {
        echo $child->name, ': ', $child->value, "\n";
    }
}
```

It is also `Countable`, `ArrayAccess` and `IteratorAggregate`, so all of
these work:

```php
\count($token);   // how many tokens the inner lexer read
$token[0];        // the first of them
foreach ($token as $child) { /* ... */ }
```

Two things about the values:

```php
$token->value; // '"'  — only the token's own text
$token->end;   // 11   — but it spans everything the inner lexer read
```

`value` is the quote that opened the string. `end` is where the string
*finished*. That is why `end` exists as a property instead of being computed
from the value.

## In A Grammar File

The same thing in `.pp3` is written with states. A token declaration can name
the state it switches to after `->`, and a token declared as `state:NAME`
belongs to that state:

```pp2
%token        T_QUOTE_OPEN  "      -> string
%token string:T_TEXT        [^"]++
%token string:T_QUOTE_CLOSE "      -> default
%skip         T_WHITESPACE  \s++

Str : <T_QUOTE_OPEN> ;
```

Switching from `default` into a named state is "enter"; switching from a
named state back to `default` is "exit". Jumping straight from one named
state to another is not expressible — the reading is nested, so it can only
go one level down and back up.

Parsing `"hello"` gives you a `TokenEmbedding`:

```php
$result = $parser->parse(new Source('"hello"'));

echo $result->value;          // '"'
echo $result->children[0]->value; // 'hello'
```

## Bringing Your Own Lexer

The nested lexer does not have to be built by phplrt. Any
`LexerInterface` will do, which is how you embed a language phplrt cannot
describe with regular expressions — PHP itself, for instance:

```php
use Phplrt\Contracts\Lexer\LexerInterface;

final class PhpTokenLexer implements LexerInterface
{
    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        // Read with token_get_all(), yield tokens
    }
}

$builder->addPattern('<\?php', 'T_PHP_OPEN')
    ->enter('php');
$builder->addEmbeddedLexer('php', new PhpTokenLexer());
```

Such a lexer decides on its own where its fragment ends: it simply stops, and
control returns to the lexer that called it. It does not need an "exit"
token, and its own terminal token is not carried over.

This is also the escape hatch for anything a regular expression cannot do:
heredocs, indentation-sensitive blocks, nested comments.

## When To Reach For This

Use a nested lexer when a fragment has **different lexical rules** than its
surroundings:

- string literals with escapes and interpolation;
- comments (especially nested ones);
- embedded languages: SQL in a string, CSS in a `<style>`, PHP in HTML;
- raw or verbatim blocks in a template language.

Do *not* use it for things the grammar can handle. A parenthesized expression
is not a different language — the tokens inside it are the same tokens. That
belongs in a parser rule, not a lexer state.
