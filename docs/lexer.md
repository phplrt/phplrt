# Lexer

> This package can be installed separately using the command `composer require phplrt/lexer`

In order to quickly understand how it works:

```php {highlight: ['2-4', 9]}
$tokens = [
    'T_WHITESPACE'  => '\s+',
    'T_PLUS'        => '\+',
    'T_DIGIT'       => '\d+'
];

$lexer = new Phplrt\Lexer\Lexer($tokens);

foreach ($lexer->lex('23 + 42') as $token) {
    echo $token . "\n";
}

//
// Expected output:
//
// > "23" (T_DIGIT)
// > " " (T_WHITESPACE)
// > "+" (T_PLUS)
// > " " (T_WHITESPACE)
// > "42" (T_DIGIT)
// > \0
//
```

The lexer's `lex()` method returns an iterator of 
`Phplrt\Contracts\Lexer\TokenInterface` objects and the phplrt 
`Phplrt\Lexer\Token` implementation of this interface allows you to render 
these objects as a string value.

## Tokens Exclusion

The second argument to the `Lexer` class is the list of token names that are
ignored in the `lex` method result. Let's exclude the whitespace from the result.

```php{highlight: [3]}
<?php
$lexer = new Phplrt\Lexer\Lexer(..., [
    'T_WHITESPACE'
]);

foreach ($lexer->lex('23 + 42') as $token) {
    echo $token . "\n";
}

//
// Expected output:
//
// > "23" (T_DIGIT)
// > "+" (T_PLUS)
// > "42" (T_DIGIT)
// > \0
//
```

![/img/docs/lexer-tokens.png](/img/docs/lexer-tokens.png)

We have added a `T_WHITESPACE` to ignored lexemes that's why we only got two 
significant tokens `T_DIGIT` and one `T_PLUS`. Although this is not entirely 
true, the answer contains a `T_EOI` (End Of Input) token which can also be 
removed from the output by adding an array of the second argument of `Lexer` 
class.

## Token Objects

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

For example, for the first `T_DIGIT` the values will be as follows:

```php
echo $token->getName();
// Excepted Output: string("T_DIGIT")

echo $token->getOffset();
// Excepted Output: int(0)

echo $token->getValue();
// Excepted Output: string("2")

echo $token->getBytes();
// Excepted Output: int(1)
```
