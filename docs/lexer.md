# Lexer

> Note: Tests can not always pass correctly. This may be due to the inaccessibility of 
PPA servers for updating gcc and g++. The lexertl build requires the support of a modern 
compiler inside Travis CI. In this case, a gray badge will be displayed with the message "Build Error".

In order to quickly understand how it works - just write ~4 lines of code:

```php
$lexer = new Phplrt\Lexer\Lexer(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE']);

foreach ($lexer->lex('23 42') as $token) {
    echo $token . "\n";
}

//
// Expected output:
//
// > "23" (T_DIGIT)
// > "42" (T_DIGIT)
// > \0
//
```

This example will read the source text and return the set of tokens from which it is composed:
  * `T_DIGIT` with value "23"
  * `T_DIGIT` with value "42"

The second argument to the `Lexer` class is the list of token names that are ignored in the `lex` method result. 
That's why we only got two significant tokens `T_DIGIT`. Although this is not entirely true,
the answer contains a `T_EOI` (End Of Input) token which can also be removed from the output 
by adding an array of the second argument of `Lexer` class.

...and now let's try to understand more!

```php
use Phplrt\Lexer\Lexer;

/**
 * List of available tokens in format "name => pcre"
 */
$tokens = ['T_DIGIT' => '\d+', 'T_WHITESPACE' => '\s+'];

/**
 * List of skipped tokens
 */
$skip   = ['T_WHITESPACE'];

/**
 * Create lexer and tokenize sources. 
 */
$lexer = new Lexer($tokens, $skip, Phplrt\Lexer\Driver\Markers::class);
```

In order to tokenize the source text, you must use the method `->lex(...)`, which returns 
iterator of the `TokenInterface` objects.

```php
foreach ($lexer->lex(File::fromSources('23 42')) as $token) {
    echo $token . "\n";
}
```

A `Phplrt\Contracts\Lexer\TokenInterface` provides a convenient API to obtain 
information about a token:

```php
interface TokenInterface
{
    public function getName(): string;
    public function getOffset(): int;
    public function getValue(): string;
    public function getBytes(): int;
}
```
