# Tokens and Channels

A token is the smallest thing a lexer produces. It is immutable, and it
carries five pieces of information.

```php
foreach ($lexer->lex(new Source('23 + 42')) as $token) {
    $token->id;      // 0       — which definition matched
    $token->name;    // T_DIGIT — its human-readable name, or null
    $token->value;   // "23"    — the exact text that matched
    $token->offset;  // 0       — where it starts, in bytes
    $token->end;     // 2       — where it ends, in bytes
    $token->channel; // Channel::Default
}
```

## Identifiers, Not Names

Tokens are compared **by identifier**, never by name:

```php
if ($token->id === MyParser::T_DIGIT) {
    // ...
}
```

The identifier is just the position of the token's definition in the lexer,
assigned when the lexer is built. Names exist for you and for error messages;
the parser does not use them at all, which is why a token is allowed to have
no name:

```php
$builder->addPattern('\s++')->hide(); // anonymous, and that is fine
```

System tokens use negative identifiers, so they can never collide with yours:

| Token             | Identifier | Channel      |
|-------------------|------------|--------------|
| `EndOfInputToken` | `-1`       | `EndOfInput` |
| `UnknownToken`    | `-2`       | `Unknown`    |

## Offsets

`offset` and `end` are **byte** offsets, not character offsets, and `end` is
the position immediately *after* the token:

```php
$content = $source->content;

// The exact fragment the token was read from
$text = \substr($content, $token->offset, $token->end - $token->offset);
```

For an ordinary token `end` is simply `offset + strlen($value)`. It differs
only for a token that [entered a nested lexer](/docs/lexer/embedding), where
the token spans everything that lexer read.

## Printing

Tokens are `Stringable`, and they print in a form meant for error messages:

```php
echo $token;
```

```
"23" (T_DIGIT)      // a named token
"@" (unknown token) // unrecognized input
end of input        // the terminal token
```

Long values are cut off, and control characters are escaped, so you can put a
token straight into a message without worrying about what is in it.

## Channels

A channel is a label on a token. The lexer emits everything, and the channel
decides who gets to see it.

There are four built-in channels:

| Channel      | Meaning                                                    |
|--------------|------------------------------------------------------------|
| `Default`    | An ordinary token. The parser reads these.                  |
| `Hidden`     | Recognized, but not passed to the parser: whitespace, comments. |
| `Unknown`    | Text the lexer did not recognize.                           |
| `EndOfInput` | The terminal token, exactly one per stream.                 |

```php
use Phplrt\Contracts\Lexer\Channel;

$builder->addPattern('\s++')->setChannel(Channel::Hidden);
$builder->addPattern('\s++')->hide();  // the same, shorter
$builder->addPattern('\s++')->show();  // back to Default
```

### Custom Channels

Hiding a token throws it away. Sometimes you want to *keep* it, just not in
the grammar — documentation comments are the usual example. Give it a channel
of its own:

```php
$builder->addPattern('\d++', 'T_DIGIT');
$builder->addPattern('//[^\n]*+', 'T_COMMENT')->setChannel('comments');
$builder->addPattern('\s++')->hide();

foreach ($lexer->lex(new Source("1 // hi\n2")) as $token) {
    echo $token->name, ' on ', $token->channel->name, "\n";
}
```

```
T_DIGIT on Default
T_COMMENT on comments
T_DIGIT on Default
```

The parser skips everything that is not on `Default`, so `T_COMMENT` never
reaches your grammar — but it is right there in the token stream if a
documentation generator wants it.

### Choosing What The Parser Sees

The parser filters the stream before it starts. By default it drops the
`Hidden` channel and keeps the rest. Pass your own filter to change that:

```php
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Parser\Internal\Filter\ChannelFilter;

$parser = new Parser(
    lexer: $lexer,
    grammar: $grammar,
    initial: 0,
    // Let the hidden tokens through after all
    filter: new ChannelFilter([]),
);
```

`ChannelFilter` compares channels by identity, which works for the built-in
ones (they are enum cases) but *not* for custom ones: every lexer builds its
own `UserDefinedChannel` instances, so the one you construct is never the one
the token carries. Match on the name instead:

```php
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Parser\Internal\Filter\FilterInterface;

final class HiddenChannelsFilter implements FilterInterface
{
    /**
     * @var list<non-empty-string>
     */
    private readonly array $excluded;

    public function __construct(string ...$channels)
    {
        $this->excluded = [Channel::Hidden->name, ...$channels];
    }

    public function apply(iterable $tokens): iterable
    {
        foreach ($tokens as $token) {
            if (!\in_array($token->channel->name, $this->excluded, true)) {
                yield $token;
            }
        }
    }
}
```

```php
$parser = new Parser(
    lexer: $lexer,
    grammar: $grammar,
    initial: 0,
    filter: new HiddenChannelsFilter('comments'),
);
```

## Captures

If a token's pattern has capturing groups, whatever they matched is on the
token:

```php
$builder->addPattern('"([^"]*)"', 'T_STRING');
$builder->addPattern('(\d++)\.(\d++)', 'T_FLOAT');

// "hi" 3.14
$string->captures; // ["hi"]
$float->captures;  // ["3", "14"]
```

Captures are numbered per token, starting at zero — the first group of *this*
token is `captures[0]`, no matter how many groups the tokens above it have.

This saves you from parsing the value twice:

```php
// Instead of trimming the quotes off $token->value by hand
$content = $token->captures[0];
```

A group that matched nothing still counts, so the numbering stays stable:

```php
$builder->addPattern('(\+|-)?(\d++)', 'T_NUMBER');

// "42"  => captures: ["", "42"]
// "-42" => captures: ["-", "42"]
```

## The Interface

Write against the contract, not the implementation:

```php
use Phplrt\Contracts\Lexer\TokenInterface;

function describe(TokenInterface $token): string
{
    return \sprintf('%s at %d', $token->name ?? 'anonymous', $token->offset);
}
```

`Phplrt\Lexer\Token\Token` is the standard implementation;
`TokenEmbedding` extends it for [nested lexers](/docs/lexer/embedding).
