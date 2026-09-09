# Nested lexers reference (Phplrt embedded lexers)

Plain reference reached from the `phplrt` skill. Full surface: https://phplrt.org/llms.txt. This file is the part not obvious from the API.

> Packages you need: see **Pre-flight** in `../SKILL.md` (nested lexers need `phplrt/lexer-builder` + `phplrt/source`).

## The mechanism

```php
$lexer = new LexerBuilder();
$lexer->disable(RegexModifier::DotAll);

// Opening token ENTERS a sub-lexer.
$fm = $lexer->addPattern('\A---+[^\n]*\n?', 'T_FM_DELIM')->enter('frontmatter');

$sub = $lexer->addLexer('frontmatter');
$sub->addPattern('^---+[^\n]*\n?', 'T_FM_DELIM_CLOSE')->exit(); // EXIT back to parent
$sub->addPattern('^[A-Za-z_][\w-]*:', 'T_FM_KEY');
$sub->addPattern('[^\n]*\n?', 'T_FM_VALUE');
```

- `->enter('lang')` switches the active lexer to the named sub-lexer at that token; `->exit()` returns to the parent.
- The **opening token becomes a `TokenEmbedding`**. The parser references `T_FM_DELIM` (by the `TokenDefinition` object) and reads the fragment from `$token->children`, never from inner token names.
- Each child has `->name` and `->value`. Walk them in the reducer.

## Read the embedding

```php
$frontmatter = $builder->addTokenReference($fm)   // the TokenDefinition from addPattern
    ->setReducer(static function ($ctx, $token) use ($inline) {
        $attrs = [];
        $key = null;
        foreach ($token->children as $child) {
            switch ($child->name) {
                case 'T_FM_KEY':
                    $key = rtrim($child->value, ':');
                    $attrs[$key] = '';
                    break;
                case 'T_FM_VALUE':
                    if ($key !== null) { $attrs[$key] .= trim($child->value); }
                    break;
                case 'T_FM_DELIM_CLOSE':
                    break 2;                        // stop at the closing line
            }
        }
        return new Node('frontmatter', $attrs);
    });
```

- Inside a sub-lexer, `^` matches the **start of each line** (multiline is default) — but `\A` in the parent matches only the file start, so the frontmatter opens only at the top.
- Hidden tokens inside the sub-lexer (e.g. a comment) still appear in `$token->children`; skip them by name.

## Fenced code block — same shape

```php
$code = $lexer->addPattern('^```[^\n]*\n?', 'T_FENCE')->enter('code');
$sub  = $lexer->addLexer('code');
$sub->addPattern('^```[^\n]*\n?', 'T_FENCE_CLOSE')->exit();
$sub->addPattern('[^\n]*\n', 'T_CODE_LINE');

$codeBlock = $builder->addTokenReference($code)
    ->setReducer(static function ($ctx, $token) {
        $src = '';
        foreach ($token->children as $child) {
            if ($child->name === 'T_FENCE_CLOSE') { break; }
            $src .= $child->value;
        }
        return new Node('code_block', ['code' => $src]);
    });
```

## When a regex sub-lexer isn't enough: a hand-written `LexerInterface`

`enter()`/`addLexer()` above is still a *regex* sub-lexer — every token in it is a pattern
declared up front. That falls apart the moment where a fragment ends depends on something only
known at match time, not at declaration time: how far a list item or a blockquote is
indented, for instance. Indentation is a column, and a lexer state is a fixed, named thing —
there is no way to declare "the state for column 7" ahead of the input.

For that, embed a real class instead of another regex sub-lexer:

```php
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Contracts\Lexer\Channel;

final class ListItemLexer implements LexerInterface
{
    public function __construct(private readonly int $id) {}

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        // Read $source->content from $offset onward by hand: measure the
        // marker's own column right here (it is only known now, from where
        // this particular item happened to start), decide how many
        // subsequent lines belong to this item by indentation, and yield
        // whatever Token/EndOfInputToken objects the grammar expects.
        // Nothing about this needs a pattern declared in advance.
        yield new Token(id: $this->id, name: 'T_ITEM_BODY', channel: Channel::Default, value: '...', offset: $offset);
        yield new EndOfInputToken($offset);
    }
}
```

```php
$marker = $lexer->addPattern('^ {0,3}[-*+][ \t]+', 'T_LIST_MARKER')->enter('item');
$lexer->addEmbeddedLexer('item', new ListItemLexer($someTokenId));
```

- `addEmbeddedLexer(string $name, LexerInterface $lexer)` registers any class implementing
  `LexerInterface` as a named lexer state, the same slot `addLexer()` fills with a regex
  sub-lexer — `enter('item')` reaches it exactly the same way.
- The class gets the *whole* source and an offset; it decides for itself, in plain PHP, where
  its own fragment ends and returns its tokens (ending in an `EndOfInputToken`) — there is no
  regex layer underneath it at all.
- This is how phplrt's own `.pp3` compiler reads a reducer's block of PHP code
  (`PhpBlockLexer`, tracking brace depth by hand) and it is the only way to read Markdown's
  own list/blockquote nesting or YAML-style indentation: measure the column right there in the
  lexer, since no declared pattern can stand in for "however far *this* one happens to be
  indented."
- Recursion into a nested block's own structure (a list item that itself contains a list) is
  not a lexer-state concept either — it is the reducer calling the *same* parser again on the
  dedented fragment the embedded lexer handed it, tracking each line's true original offset
  itself rather than a single before/after shift (a line loses a different number of bytes
  than its neighbor once dedented, so one flat offset correction drifts as soon as there is
  more than one line).

### The same thing in a `.pp3` grammar (so it survives generation)

`addEmbeddedLexer()` is the builder-API form. If you are shipping — i.e. generating a committed
parser (see the SKILL's "Ship the runtime") — put the hand-written lexer *in the grammar*
instead, so the one generated class stays self-contained. A token's action enters a state, and
`%lexer` binds a class to that state:

```
%token T_LIST_MARKER  ^\h{0,3}[-*+]\h+  -> state(item)
%lexer  item -> { new \App\ListItemLexer() }
```

This is exactly how phplrt's own pp3 grammar embeds `PhpBlockLexer`
(`%lexer php -> { new \Phplrt\Compiler\Syntax\Common\PhpBlockLexer() }`). The generated parser
calls `new \App\ListItemLexer()` in its constructor — that class needs only the runtime
(`Phplrt\Contracts\Lexer\LexerInterface`), so nothing about the codegen path drags a builder or
the compiler into production. A hand-written lexer bolted on *after* `getParser()` at runtime,
by contrast, is the anti-pattern the SKILL warns about: it cannot be generated away.

## Why this saves context

One opening token carries the whole nested fragment; the grammar stays flat (it only names the opener) and the reducer holds the sub-format logic in one place. The outer parser never learns the inner language — that is the decomposition: each format lives behind its own `enter`, so an agent editing the code fence need not touch the frontmatter rule and vice versa.

See `reference/lexer.md` for modifier/channel details and `reference/parser.md` for reducer value shapes.
