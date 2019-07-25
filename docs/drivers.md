# Drivers

The factory returns one of the available implementations, however you can create it yourself.

## PCRE

`NativeRegex` implementation is based on the built-in php PCRE functions.

```php
use Phplrt\Lexer\Driver\NativeRegex;
use Phplrt\Source\File;

$lexer = new NativeRegex(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE', 'T_EOI']);

foreach ($lexer->lex(File::fromSources('23 42')) as $token) {
    echo $token->getName() . ' -> ' . $token->getValue() . ' at ' . $token->getOffset() . "\n";
}

// Outputs:
// T_DIGIT -> 23 at 0
// T_DIGIT -> 42 at 3
```

## Lexertl

Experimental lexer based on the 
[C++ lexertl library](https://github.com/BenHanson/lexertl). To use it, you 
need support for [Parle extension](http://php.net/manual/en/book.parle.php).

```php
use Phplrt\Lexer\Driver\ParleLexer;
use Phplrt\Source\File;

$lexer = new ParleLexer(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE', 'T_EOI']);

foreach ($lexer->lex(File::fromSources('23 42')) as $token) {
    echo $token->getName() . ' -> ' . $token->getValue() . ' at ' . $token->getOffset() . "\n";
}

// Outputs:
// T_DIGIT -> 23 at 0
// T_DIGIT -> 42 at 3
```

> Be careful: The library is not fully compatible with the PCRE regex 
syntax. See the [official documentation](http://www.benhanson.net/lexertl.html).
