# Builder

> This package can be installed separately using the command `composer require phplrt/grammar`

In case to quickly compose a grammar, you can use the special grammar builder, 
class which provides a convenient API for creating grammar rules.

> In addition to this method, phplrt provides an alternative option with 
> declarative EBNF-like grammar, which can be found on
> [this documentation page](/docs/compiler/grammar).

## Code

Let's take an example of [PP2 grammar](/docs/compiler/grammar):

```ebnf
expr = <T_DIGIT> (::T_PLUS:: <T_DIGIT>)*
```

And example of equivalent built using the grammar builder class.

```php
<?php

use Phplrt\Parser\Grammar\Builder;

$grammar = new Builder(function (Builder $ctx) {
    yield $ctx->concat(
        // <T_DIGIT>
        $digit = yield $ctx->token('T_DIGIT'),

        // (::T_PLUS:: <T_DIGIT>)*
        yield $ctx->repeat(yield $ctx->token('T_PLUS', false), $digit)
    );
});
```

In case to check the selected grammar, just pass it as constructor's argument 
to the parser.

```php
<?php

use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Grammar\Builder;
use Phplrt\Parser\Parser;

$lexer = new Lexer(...);

$parser = new Parser($lexer, new Builder(function () {
    // Grammar Rules
}));
```
