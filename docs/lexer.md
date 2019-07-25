# Lexer

> Note: Tests can not always pass correctly. This may be due to the inaccessibility of 
PPA servers for updating gcc and g++. The lexertl build requires the support of a modern 
compiler inside Travis CI. In this case, a gray badge will be displayed with the message "Build Error".

In order to quickly understand how it works - just write ~4 lines of code:

```php
$lexer = Phplrt\Lexer\Factory::create(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE']);

foreach ($lexer->lex(Phplrt\Source\File::fromSources('23 42')) as $token) {
    echo $token . "\n";
}
```

This example will read the source text and return the set of tokens from which it is composed:
  * `T_DIGIT` with value "23"
  * `T_DIGIT` with value "42"

The second argument to the `Factory` class is the list of token names that are ignored in the `lex` method result. 
That's why we only got two significant tokens `T_DIGIT`. Although this is not entirely true,
the answer contains a `T_EOI` (End Of Input) token which can also be removed from the output 
by adding an array of the second argument of `Factory` class.

...and now let's try to understand more!

```php
use Phplrt\Lexer\Factory;

/**
 * List of available tokens in format "name => pcre"
 */
$tokens = ['T_DIGIT' => '\d+', 'T_WHITESPACE' => '\s+'];

/**
 * List of skipped tokens
 */
$skip   = ['T_WHITESPACE'];

/**
 * Options:
 *   0 - Nothing.
 *   2 - With PCRE lookahead support.
 *   4 - With multistate support.
 */
$flags = Factory::LOOKAHEAD | Factory::MULTISTATE;

/**
 * Create lexer and tokenize sources. 
 */
$lexer = Factory::create($tokens, $skip, $flags);
```

In order to tokenize the source text, you must use the method `->lex(...)`, which returns 
iterator of the `TokenInterface` objects.

```php
foreach ($lexer->lex(File::fromSources('23 42')) as $token) {
    echo $token . "\n";
}
```

A `TokenInterface` provides a convenient API to obtain information about a token:

```php
interface TokenInterface
{
    public function getName(): string;
    public function getOffset(): int;
    public function getValue(int $group = 0): ?string;
    public function getGroups(): iterable;
    public function getBytes(): int;
    public function getLength(): int;
}
```
