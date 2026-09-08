# PP3 grammar reference (the shipping path's own language)

Plain reference reached from the `phplrt` skill. Full syntax: https://phplrt.org/llms.txt (PP3 Grammar Syntax). This file keeps the decisions and traps.

> Needs `phplrt/compiler` (dev-only — see **Pre-flight** in `../SKILL.md`). Verify with `vendor/bin/phplrt check`, generate with `vendor/bin/phplrt compile` (see the SKILL's "Definition of done").

## Skeleton

```pp3
%skip  T_WHITESPACE  \s++
%token T_NUMBER      \d++
%token T_PLUS        \+

%pragma root Sum

Sum -> { return \array_sum($children); }
  : Number() (::T_PLUS:: Number())*
  ;

Number -> { return (int) $children->value; }
  : <T_NUMBER>
  ;
```

- Naming convention (unenforced, universal): tokens `T_SCREAMING_CASE`, rules `PascalCase`, fragments `SCREAMING_CASE` without the `T_`.
- **Always set `%pragma root`.** The default is "first rule declared", which silently changes when an `%include` lands above it — the optimizer then drops everything unreachable from the wrong root. `check -v` exposes it: `Rules: Loaded: 7 / After: 1` means the root is wrong, not that the optimizer did well.

## Tokens — the traps

- **A declaration is one line, and the pattern cannot contain a literal space** — whitespace is what separates the parts. Write `\x20` or `\s`.
- **Order wins, not length** (the same rule as the builders): `%token T_POW \*\*` before `%token T_STAR \*`; keywords before the `T_NAME` that would swallow them.
- `%skip T_WS \s++` is shorthand for `-> channel(Hidden)`: the token is still recognized (offsets stay correct) but never reaches the parser.
- `%fragment NAME re` names a pattern piece; `(?&NAME)` writes it into a token. A fragment is no token — no state, no action — and its declaration order does not matter (resolved after the whole grammar is read).

## Rules

- `<T_X>` reads a token and **keeps** it in the result; `::T_X::` reads it and **discards** it. Keep what carries information, discard the punctuation that only holds syntax together.
- `Rule()` — the parentheses are what mark a rule reference; the rule may be declared later or in another file.
- Inline tokens: `"+"` is literal text (nothing inside is special), `/re/` is a pattern. An inline token is always discarded; declare a real token for anything used more than twice so error messages can name it. A lone slash is `/\//` — bare `//` opens a comment.
- Quantifiers after any token, rule, or group: `? * + {3} {2,5} {2,} {,5}`.
- Predicates look ahead without consuming: `&e` goes on only if `e` matches next, `!e` only if it does not; nothing lands in the tree. `Variable : <T_NAME> !::T_LP:: ;` reads a name that is not a call — and leaves the `(` for whoever wants it. Look ahead at a token, not a rule: `!Expression()` really parses a whole expression just to throw it away.

## PEG semantics — grammar-authoring traps

- **Choice is ordered.** `Rule : "a" | "ab" ;` never reads `ab` — the first alternative that matches wins and the parser does not go back for a longer one. Longer alternative first.
- **Left recursion is refused at compile time.** `Expr : Expr() ::T_PLUS:: Number() ;` never terminates; the standard translation is a repetition: `Expr : Number() (::T_PLUS:: Number())* ;`.
- **The whole input must be consumed.** Trailing junk is a syntax error, not a stopping point.

## Reducers

`-> { ... }` sits between the rule name and the colon; the block returns the node. `$children` is the important variable: an **array** for a sequence rule (concatenation/repetition), a **single value** for anything else — and nested arrays flatten into the parent, so a rule that should produce a group must return an object, never a bare array (the same trap `reference/parser.md` spells out for the builder API). Other variables — `$ctx`, `$offset`/`$begin`, `$end`, `$length`, `$source`, `$content`, `$rule` — are expanded by the compiler only if used, so they cost nothing.

Class names inside reducers: pass `--use "App\Ast\Node"` to `compile` so the grammar can write `new Node(...)` instead of the FQCN.

## Custom error messages — `@error`

`@error("message")` replaces the default `Syntax error, unexpected ...` when the thing it is written on fails to match. It rides along into the generated parser (compiled into its message table), so it works in the shipped runtime — no compiler needed at parse time.

```pp3
Root : ::T_OPEN:: Pair() @error("a pair of names is expected") ::T_CLOSE:: ;
Pair @error("write it as name, name") : <T_NAME> ::T_COMMA:: <T_NAME> ;
```

- Write it **after** the element it annotates — a token, a `Rule()` reference, a group, or a quantified thing (`Number()* @error(...)`) — or after the rule's own name, before the `:` and before any `-> { reducer }`. On a predicate it annotates the lookahead: `&<T_NAME> @error("a name is expected")`.
- **The innermost / furthest failure wins.** An `@error` on an outer rule does not mask a more specific one deeper in, and a plain syntax error that got *further* into the input still beats a shallower `@error` — the message reports the real failure point, same "furthest position reached" rule as `reference/parser.md` describes. So an `@error` fires only when its element is genuinely where parsing stalled.
- Placeholders, filled from the token parsing stopped on: `{name}` (token name), `{line}`, `{column}`, `{expected}` (shortened token list), `{expected_list}` (full list). A literal brace is doubled: `{{name}}` → `{name}`.
- Compile-time checks: exactly one non-empty argument, and not two `@error` on the same rule — either is a `CompilationFailedException` / `UnsupportedAnnotationException` at `check`.

## States and embedded lexers

- `%token string:T_TEXT [^"]++` declares a token in state `string`; the actions `-> state(string)` and `-> exit()` move the reading in and out. `*:T_X` adds a token to every state (tried after the state's own tokens).
- `%lexer name -> { new \App\MyLexer() }` binds a hand-written `LexerInterface` to a state. The body is an **expression** — no `return`, no semicolon. Such a lexer decides where its fragment ends by itself, so it needs no `exit()` token. See `reference/nested.md`.

## %include

`%include grammar/lexemes` — the path is relative to the including file, the extension may be omitted, and a file included from several places is read once. Declarations land exactly where the `%include` stands — which is how the default root goes wrong; `%pragma root` again.
