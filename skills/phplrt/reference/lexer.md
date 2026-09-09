# Lexer reference (Phplrt LexerBuilder)

Plain reference reached from the `phplrt` skill. The full surface is at https://phplrt.org/llms.txt — this file keeps only decisions and traps.

> Packages you need: see **Pre-flight** in `../SKILL.md` (lexer branch → `phplrt/lexer-builder` + `phplrt/source`).

## Declare tokens

```php
use Phplrt\Lexer\Builder\LexerBuilder;

$lexer = new LexerBuilder();
$lexer->addPattern('[A-Za-z_]\w*', 'T_WORD');   // regex, token name
$lexer->addValue('+', 'T_PLUS');                 // literal, safer than a regex
$lexer->addPattern('\s+')->hide();               // skip, keep in stream off
```

- **Declaration order wins, not length.** The generated regex is a single PCRE alternation (`\G(?|pattern1|pattern2|...)`, one branch per token in the order added), and PCRE alternation is leftmost-first: the *first* pattern that matches at the current position wins, even if a later one would have matched more characters. Put a keyword or a multi-char pattern *before* the generic rule it would otherwise be swallowed by (a keyword before an identifier pattern that also matches it) — there is no length-based fallback to rescue a rule declared too late.
- `addValue($lit, $name)` builds a literal pattern; prefer it for punctuation so you never debug an escaped regex.

## PCRE modifiers

```php
use Phplrt\Lexer\Builder\Definition\RegexModifier;

$lexer->disable(RegexModifier::DotAll);   // '.' stops matching newlines (line lexing)
$lexer->enable(RegexModifier::Multiline); // '^'/'$' match line starts (usually default)
$lexer->enable(RegexModifier::Caseless);  // case-insensitive tokens
```

- **DotAll is ON by default** — disable it for line-based formats or your `[^\n]+` will swallow the file.
- A sub-lexer (nested) has its own modifier state; set it on the sub-lexer too.

## Channels / hidden tokens

```php
$lexer->addPattern('\s+', 'T_WS')->hide();
```

- Hidden tokens are removed from the parser's token stream but **remain in an embedding's `children`** if a sub-lexer produced them. Skip them by name when walking children.
- Use hiding for whitespace/comments you don't want as grammar tokens. Do not `hide()` tokens the grammar must reference.

## Nested lexers (enter a sub-lexer)

```php
$lexer->addPattern('"', 'T_DQUOTE_OPEN')->enter('string');
$string = $lexer->addLexer('string');
$string->addPattern('[^"]+', 'T_STR_CHARS');
$string->addPattern('"', 'T_DQUOTE_CLOSE')->exit();
```

- `->enter('name')` switches to the named sub-lexer at that token; `->exit()` returns to the parent.
- The opening token becomes a `TokenEmbedding`; the parser references it and walks `$token->children`.
- See `reference/nested.md` for the frontmatter / fenced-code recipe and how the `TokenEmbedding` is read.

## Build

```php
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Source\StringSource;

$lexerResult = $lexer->build();
$runtimeLexer = (new RuntimeLexerTransformer())->transform($lexerResult);
foreach ($runtimeLexer->lex(StringSource::createFromString($src)) as $tok) {
    // $tok->name, $tok->value; embedded fragments are TokenEmbedding
}
```

Hand `$lexerResult` and `$runtimeLexer` to `ParserBuilder` (see `reference/parser.md`): `$parser = $builder->build($lexerResult)->toParser($runtimeLexer);`.

`build()` here is a **dev-time** step (it needs `phplrt/lexer-builder`). A standalone lexer you only use for tokenizing is usually a prototype; if it feeds a parser you ship, the whole thing gets generated into one runtime-only class — don't keep `build()` on the request path in production. See the SKILL's "Ship the runtime".
