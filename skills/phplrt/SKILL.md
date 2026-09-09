---
name: phplrt
description: Build a lexer/parser for a text format (config, DSL, markup) with phplrt, and ship it right — describe a grammar (`.pp3` file, or `LexerBuilder`/`ParserBuilder` in PHP), GENERATE a committed parser class, and depend only on the runtime; the compiler and builders are dev-only. Use when you must tokenize or parse text into a typed AST. Branches: lexer (reference/lexer.md), parser + reducers (reference/parser.md), the `.pp3` grammar language (reference/pp3.md), nested/embedded lexers (reference/nested.md).
---

phplrt reads a text format into a value or AST. `LexerBuilder` turns characters into tokens; `ParserBuilder` orders those tokens and runs reducers that build your result; a `.pp3` grammar file is the same thing written as a DSL. Two leading words decide the branch:

- **_lexer_** — cut text into tokens (regex per token, optional sub-lexers).
- **_parser_** — order those tokens and reduce them into a value/AST.

The full API lives in the source of truth: https://phplrt.org/llms.txt — do not memorize it; read it when a method name is missing. This skill carries only the decisions and gotchas that are not in `--help`.

## Ship the runtime, not the toolchain — read this first

This is the mistake that produces a large pile of code that cannot run in production. Get it right before writing anything.

phplrt splits in two:

- **Runtime** (`phplrt/runtime` = `phplrt/lexer` + `phplrt/parser` + `phplrt/source`) — what a shipped parser needs, and *all* it needs. A shipped parser is a plain PHP class that `extends \Phplrt\Parser\Parser`.
- **Build-time describers** that turn a grammar into that class: the `.pp3` language read by `phplrt/compiler`, and the in-PHP `phplrt/lexer-builder` + `phplrt/parser-builder`. **Every one of these is a dev dependency.** `phplrt/compiler` alone drags in `laminas/laminas-code`, `symfony/console`, and `twig` — none of which belong in production.

**The anti-pattern (do not do this):** calling `(new Compiler())->...->getParser()`, or `$builder->build()->toParser()`, at request time. It forces a describer into `require`, recompiles the whole grammar on every boot, and in a real deploy — where the compiler is `require-dev` and simply isn't installed — fatals with a "class not found". "Give me a parser from what I just built" is a dev-loop convenience, never a shipping architecture.

**The correct flow — generate once, commit the output, depend only on the runtime:**

```bash
composer require phplrt/runtime            # production   (ask the user first — see Pre-flight)
composer require --dev phplrt/compiler     # dev only     (ask the user first — see Pre-flight)

# generate a committed parser from the grammar (dev step; re-run when the grammar changes)
vendor/bin/phplrt check grammar.pp3 -v   # exit 0 = the grammar compiles; read the numbers (see pp3.md)
vendor/bin/phplrt compile grammar.pp3 src/MyParser.php \
    --class=MyParser --namespace='App\Parser'
```

```php
// runtime: no compiler, no builders, no laminas/symfony/twig
$parser = new App\Parser\MyParser();
$ast = $parser->parse(Phplrt\Source\StringSource::createFromString($input));
```

The generated class inlines the whole lexer as one regex and every reducer as a real method you can step through — there is no hidden runtime compilation left. `(new Compiler())->load(new FileSource('grammar.pp3'))->generate()->withClassName(...)->withNamespaceName(...)->save('src/MyParser.php')` is the same thing from PHP if you would rather generate from a build script than the CLI. (Wrap the `new` in parens: phplrt 4 runs on PHP 8.1+, and the parenthesis-free `new Compiler()->load(...)` chain is 8.4-only syntax that fatals on 8.1–8.3.)

**Corollary — don't fight the codegen.** If you compile a grammar into a `ParserBuilderResult` and then bolt a hand-written lexer onto it at runtime, or keep a static-cached `getParser()`, you have turned a code generator into a runtime interpreter — the same dead-weight trap, most of the code doing work `save()` would have done once. Put the hand-written lexer *inside* the grammar (`%lexer name -> { new App\MyLexer() }`, or a lexer state — see `reference/nested.md`) so one generated class is self-contained, then generate it. And reduce rules into **typed AST nodes**, not one generic catch-all node — a parser that returns a single `Node` type for everything threw away the structure that was the point of parsing.

The in-PHP builders are for prototyping a grammar and for the rare parser assembled dynamically at runtime. If you truly ship builder-constructed parsers you accept `phplrt/lexer-builder` + `phplrt/parser-builder` as runtime deps and a per-boot build — lighter than the compiler, still not the default. When in doubt: `.pp3` + generate.

## Pre-flight — install what you use

Phplrt is split into components; each class lives in its own package. A "class not found" is a missing package, not a typo — sort out what the branch needs before writing code. Checking is yours; **installing is the user's call — never run `composer require` without asking them first.** When a package the branch needs is missing, stop and ask, naming the package, its scope (`--dev` or runtime), and one line of why — what class or step needs it and what it drags in, both straight from the table below. Install only after they agree.

**Split it by where it runs (see "Ship the runtime" above):** the describer packages below — the `*-builder` pair and `phplrt/compiler` — are **dev** dependencies (`--dev`); production needs only `phplrt/runtime`. The one exception is a parser you genuinely assemble with the builders at runtime, which then keeps the `*-builder` pair in `require`.

### Lock the toolchain (PHP + composer)

Resolve the PHP and composer binaries **once, in this preparatory phase**, and reuse the absolute paths everywhere — including inside subagents, which may not share the parent shell's `PATH` and would otherwise fail with `php: command not found`.

```bash
PHP_BIN="$(command -v php || command -v php8.4 || command -v php8.3 || command -v php8.2 || command -v php8.1)"  # phplrt 4 needs 8.1+
COMPOSER="$(command -v composer || echo "$PHP_BIN $(command -v composer.phar)")"
"$PHP_BIN" -v   # confirm it actually runs
```

Pass `$PHP_BIN` (and `$COMPOSER`) to every subagent you dispatch, and invoke PHP as `"$PHP_BIN" script.php` rather than bare `php`, so execution is deterministic and survives a subagent's narrower environment. Use `$COMPOSER` in place of `composer` in the install steps below.

| You use | Package | Scope | Pulls in |
|---|---|---|---|
| A generated/shipped parser (`extends \Phplrt\Parser\Parser`), `StringSource`, `FileSource` | `phplrt/runtime` | **runtime** (`require`) | `phplrt/lexer` + `phplrt/parser` + `phplrt/source` |
| `LexerBuilder`, `RuntimeLexerTransformer`, `RegexModifier`, `TokenDefinition` | `phplrt/lexer-builder` | dev (`--dev`) | `phplrt/lexer` |
| `ParserBuilder` | `phplrt/parser-builder` | dev (`--dev`) | `phplrt/lexer-builder`, `phplrt/lexer`, `phplrt/parser` |
| `.pp3` `Compiler`, `vendor/bin/phplrt compile` (codegen) | `phplrt/compiler` | dev (`--dev`) | all of the above **+ laminas-code, symfony/console, twig** |

**Step 1 — check what the branch needs (read-only, run freely):**

```bash
composer show phplrt/runtime        >/dev/null 2>&1 || echo "missing: phplrt/runtime"        # always
composer show phplrt/compiler      >/dev/null 2>&1 || echo "missing: phplrt/compiler"       # .pp3 + generate branch
composer show phplrt/lexer-builder >/dev/null 2>&1 || echo "missing: phplrt/lexer-builder"   # lexer branch (incl. nested sub-lexers)
composer show phplrt/parser-builder >/dev/null 2>&1 || echo "missing: phplrt/parser-builder" # parser branch (pulls lexer-builder)
```

**Step 2 — for each missing package, ask the user before installing.** Say what and why, e.g.: *"The parser branch needs `phplrt/parser-builder` as a dev dependency — it provides the `ParserBuilder` class for prototyping the grammar and won't ship to production. Install it?"* For `phplrt/compiler`, mention it pulls laminas-code + symfony/console + twig — the reason it must stay `--dev`. Only after the user agrees:

```bash
composer require phplrt/runtime             # the one runtime dependency
composer require --dev phplrt/<describer>   # compiler / lexer-builder / parser-builder
```

Re-enter this check for every package a reference file names before writing code that imports it — a feature added mid-task (a nested lexer inside parser work, a `.pp3` generate step after prototyping) brings its own row of the table, and its own ask.

## Mental model

1. Declare tokens: `$lexer->addPattern('\d+', 'T_NUMBER')` — the pattern is a raw PCRE body, **no delimiters** (patterns are spliced into one alternation, so a `#...#` wrapper would become literal characters). **Declaration order wins, not length** — the lexer tries patterns in the order they were added and takes the first one that matches at the current position (verified against `MarkersRegexGenerator`: it compiles a single PCRE alternation, and PCRE alternation is leftmost-first). Put a keyword or a multi-char operator *before* the generic pattern it would otherwise be swallowed by (an identifier, a single-char operator) — length never rescues a rule declared too late.
2. Declare rules + reducers: `$builder->addTokenReference('T_FOO')->setReducer(fn($ctx, $tok) => ...)`.
3. Wire: build the lexer, transform it, hand it to the parser, parse a `StringSource`.
4. **Ship:** generate the wired parser into a committed class and depend only on the runtime (see "Ship the runtime"). Steps 1–3 are the dev loop, not the deploy artifact.

## Prototype end-to-end (the dev loop)

This builds a parser in-process — right for iterating on a grammar, wrong to leave in shipped code (it keeps the builders in `require` and rebuilds every call). Once the grammar is settled, express it as `.pp3` and generate a committed parser class instead.

```php
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Builder\Transformer\RuntimeLexerTransformer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\StringSource;

$lexer = new LexerBuilder();
$lexer->addPattern('\d+', 'T_NUMBER');
$lexer->addPattern('\+', 'T_PLUS');
$lexer->addPattern('\s+')->hide();            // skipped, not dropped

$builder = new ParserBuilder();
$number = $builder->addTokenReference('T_NUMBER')
    ->setReducer(static fn($ctx, $tok) => (int) $tok->value);
$plus = $builder->addTokenReference('T_PLUS');
// every array element must be a RuleDefinition — a bare string throws a TypeError
$sum = $builder->addConcatenation([$number, $plus, $number])
    ->setReducer(static fn($ctx, $kids) => $kids[0] + $kids[2]);
$builder->setInitialRule($sum);

$lexerResult = $lexer->build();
$runtimeLexer = (new RuntimeLexerTransformer())->transform($lexerResult);
$parser = $builder->build($lexerResult)->toParser($runtimeLexer);

$result = $parser->parse(StringSource::createFromString('12 + 30')); // 42
```

The same grammar as a committed, runtime-only parser: write the tokens/rules as `grammar.pp3`, then `vendor/bin/phplrt compile grammar.pp3 src/SumParser.php --class=SumParser --namespace='App'` and ship `new App\SumParser()`. See "Ship the runtime".

## Gotchas — the cache (read before debugging)

These are the non-obvious bits that cost hours; they are not spelled out by the API surface.

- **Compiling at request time is the one that wastes a whole deliverable, not just hours** — see "Ship the runtime". If most of your parser code is wiring builders together at boot, that code is the thing `save()` replaces.
- **DotAll is on by default.** `.` matches newlines. For line-based lexing call `$lexer->disable(RegexModifier::DotAll)`. `^`/`$` match line starts by default (PCRE multiline), so anchor block patterns with `^`.
- **Reducer value shape depends on the rule type.** A `Concatenation`/`Repetition` (a `SequenceInterface`) passes a **flat array** of child values. An `Alternation`/`Optional`/`Terminal` passes a **single** value (or `[]` when absent); a kept terminal passes the `TokenInterface`. Never assume an array for a terminal.
- **A sequence's array is flattened one level into whatever contains it — including a nested rule's own array.** If rule B (a Concatenation/Repetition) sits inside rule A's own Concatenation/Repetition, B's array does not arrive as one element of A's array — it is spread into it, so A can no longer tell where B's own children ended. A rule that must produce a group of its own has to say so with a reducer that returns something that is *not* a bare array — an object, or a scalar — since only an array gets flattened this way. This bit harder than the shape rule above because it only shows up once a grammar nests two levels deep.
- **`addRepetition($rule, $max = INF, $min = 0, $name = null)` — the name is the 4th argument.** Write `->addRepetition($rule, min: 1, name: 'Foo')`. With `min: 0` the optimizer may drop a nullable alternative; use `min: 1` to keep it.
- **`addConcatenation(array $rules = [], ?string $name = null)` and `addAlternation(array $rules = [], ?string $name = null)` take the name as the 2nd argument.**
- **Nested lexers: the parser never sees inner tokens by name.** `->enter('lang')` makes the opening token a `TokenEmbedding`; walk `$token->children` (each child has `->name` and `->value`). Reference the opening `TokenDefinition` object, not the inner token. See `reference/nested.md`.
- **Hidden tokens (`->hide()`) leave the parser stream but stay inside an embedding's children** if a sub-lexer produced them — so a hidden frontmatter comment can still surface in `$token->children`; skip it by name in the reducer.
- **Reference tokens by the `TokenDefinition` returned by `addPattern`/`addTokenReference`** when you can; string names work but the object form never silently mismatches ("token not recognized").
- **PEG semantics, in the builders and in `.pp3` alike:** choice is ordered (`a | ab` never reads `ab` — longer alternative first), left recursion is refused at compile time (rewrite as a repetition), and the whole input must be consumed — trailing junk is a syntax error, not a stopping point. Details: `reference/pp3.md`.

## Definition of done — the parser ships only when all of these hold

Each criterion is an exit code, not a judgement call. A "done" that skipped one of these is the anti-pattern from "Ship the runtime" wearing a green checkmark.

1. **The grammar compiles:** `vendor/bin/phplrt check grammar.pp3 -v` exits 0. Read the `-v` numbers: `Rules: Loaded/After` collapsing to almost nothing means the root is wrong (`%pragma root` — see `reference/pp3.md`), not a good optimization.
2. **The committed parser is not stale:** re-run the exact `compile` command, then `git diff --exit-code -- src/MyParser.php` passes. A grammar edit without a regenerate fails right here — that is the check working, not an obstacle.
3. **Production is builder-free:** the describers sit in `require-dev` only, and nothing under `src/` references `Compiler`, `LexerBuilder`, or `ParserBuilder`. `composer show phplrt/runtime` succeeds.
4. **The committed class parses:** at least one test constructs the generated parser (`new App\Parser\MyParser()` — not a freshly built one) and asserts on the typed AST from a representative input.

Wire 1 and 2 into `composer.json` scripts (`grammar:check` / `grammar:build`) and CI, so the day someone edits the grammar and forgets to rebuild fails in the pull request instead of production. The full GitHub Actions / GitLab CI recipe lives at https://phplrt.org/llms.txt (Automation and CI); checking many grammars in one job needs `xargs`, not `find -exec` (find exits 0 even when a grammar fails).

## Reference files (loaded on demand)

- Lexer-only work, PCRE modifiers, channels: `reference/lexer.md`.
- Rules, reducers, the nested inline-parser trick: `reference/parser.md`.
- The `.pp3` grammar language — tokens, rules, reducers, `%pragma`, states: `reference/pp3.md`.
- Frontmatter / fenced code / strings via embedded lexers, plus hand-written `LexerInterface` lexers for indentation-sensitive nesting a regex sub-lexer can't express: `reference/nested.md`.
- Full API, examples (JSON, Cron, URL): https://phplrt.org/llms.txt.
