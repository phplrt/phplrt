# Parser reference (Phplrt ParserBuilder)

Plain reference reached from the `phplrt` skill. Full surface: https://phplrt.org/llms.txt. Decisions and traps only.

> Packages you need: see **Pre-flight** in `../SKILL.md` (parser branch → `phplrt/parser-builder` + `phplrt/source`).

## Rule shapes

```php
$builder = new ParserBuilder();

$word = $builder->addTokenReference('T_WORD')
    ->setReducer(static fn($ctx, $tok) => $tok->value);

$eq = $builder->addTokenReference('T_EQ');
// every array element must be a RuleDefinition — a bare string throws a TypeError
$pair = $builder->addConcatenation([$word, $eq, $word])
    ->setReducer(static fn($ctx, $kids) => [$kids[0], $kids[2]]);

$list = $builder->addRepetition($word, min: 1, name: 'List')
    ->setReducer(static fn($ctx, $kids) => $kids);

$alt = $builder->addAlternation([$pair, $word], 'Item');

$builder->setInitialRule($alt);
```

- **`addTokenReference($def)`** takes a `TokenDefinition` (returned by `addPattern`) or a token name string.
- **`addConcatenation(array $rules = [], ?string $name = null)`** — name is the **2nd** arg.
- **`addAlternation(array $rules = [], ?string $name = null)`** — name is the **2nd** arg.
- **`addRepetition($rule, $max = INF, $min = 0, ?string $name = null)`** — name is the **4th** arg; write `min: 1, name: 'X'`. `min: 0` lets the optimizer drop a nullable alternative — use `min: 1` to keep it.
- **`addPredicate($rule, bool $isExpected = true, ?string $name = null)`** — lookahead: matches (or, with `isExpected: false`, refuses) the rule without consuming input or contributing to the result. The builder form of `.pp3`'s `&e` / `!e`; look ahead at a token reference, not a production — a refused production is still fully parsed before being thrown away.

## Reducer value shape — the trap

The reducer's second argument depends on the rule type:

- **Concatenation / Repetition** (a `SequenceInterface`): a **flat array** of the children's reduced values, one slot per rule *reference* in the concatenation — there is no separate "discard this one's value but still require it positionally" mechanic in this API (unlike a `.pp3` grammar's `::T_X::`). Reference a token you only need to skip past (`$eq` above) without a reducer; its slot still lands in the array as the raw `TokenInterface` — just don't read that index. This is different from a `->hide()`d token (whitespace, say): that one is invisible to the *lexer's own stream*, so it never reaches the parser at all — no slot, no `[]`, nothing — rather than a token the grammar references but ignores.
- **Alternation / Optional / Terminal**: a **single** value. For an absent optional it is `[]` (an empty array, not `null`) — the same "no slot at all" behavior an omitted optional child has inside a concatenation's own array.
- A **kept terminal** (`addTokenReference`) with no reducer passes the raw `TokenInterface`; read `$token->value` / `$token->name` / `$token->children` (embedding) inside whichever reducer receives it.

Rule of thumb: sequence → array, choice/terminal → scalar.

## The flattening trap — a nested sequence's array merges into its parent's

A subtler version of the same rule: if rule B is itself a Concatenation/Repetition and sits as
*one reference* inside rule A's own Concatenation/Repetition, B's reduced array does not
arrive as one element of A's array — it is spread into it, flattened one level, so A can no
longer tell where B's own children ended and A's next child began.

```php
$pair = $builder->addConcatenation([$word, $word])           // returns e.g. ['a', 'b']
    ->setReducer(static fn($ctx, $kids) => $kids);             // a bare array — will flatten

$line = $builder->addConcatenation([$pair, $word])
    ->setReducer(static function ($ctx, $kids) {
        // $kids is ['a', 'b', 'c'] here, NOT [['a', 'b'], 'c'] — $pair's own
        // array merged straight into $line's, indistinguishable from a
        // $line that matched three plain words directly.
        return $kids;
    });
```

Fix it by making the nested rule's own reducer return something that is *not* a bare array —
an object (even a single-property one), or a scalar; only an array gets flattened this way:

```php
$pair = $builder->addConcatenation([$word, $word])
    ->setReducer(static fn($ctx, $kids) => (object) ['pair' => $kids]); // now it survives intact
```

This only shows up once a grammar nests two levels deep, so it is easy to write a rule that
works fine in isolation and only breaks when something else starts referencing it.

## Nested inline parser (markup inside a block)

When a block's text carries its own markup (`**bold**`, `` `code` ``), build a second `ParserBuilder` once (static-cache it) and call it from the outer reducer:

```php
$inline = $this->inlineParser(); // builds a LexerBuilder+ParserBuilder for `**..**` etc.
$block = $builder->addRepetition($textToken, min: 1, name: 'Paragraph')
    ->setReducer(static fn($ctx, $kids) => $inline->parse(
        StringSource::createFromString(implode("\n", $kids))
    ));
```

Keep the inline lexer free of `.`/`\n` traps: match newlines with an explicit `\r?\n` token, or `DotAll` will make `.` eat them and `T_ANY` will not match a line break.

## Build and run (dev loop) — then generate to ship

`build()->toParser()` builds the parser in-process. That is the prototyping loop; **it is not what you ship** — leaving it on the hot path keeps `phplrt/parser-builder` in `require` and rebuilds the grammar on every call. Once the grammar is settled, express it as `.pp3` and generate a committed parser class that needs only the runtime (see the SKILL's "Ship the runtime").

```php
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Source\StringSource;

$lexerResult = $lexer->build();
$runtimeLexer = (new RuntimeLexerTransformer())->transform($lexerResult);
$parser = $builder->build($lexerResult)->toParser($runtimeLexer);

$tree = $parser->parse(StringSource::createFromString($src));
```

- Pass the **same** `$lexerResult` to `build()` and the **transformed** lexer to `toParser()`.
- Wrap input in `StringSource::createFromString($src)` (or `FileSource::createFromPathname($path)`).
- Frontmatter / fenced code / strings belong in a nested lexer — see `reference/nested.md`.
- **Reduce to typed AST nodes**, not one generic node for everything — a tree that collapses every rule into a single `Node` type has thrown away the structure parsing existed to recover.
