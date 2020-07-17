# Lexer Drivers

A driver is an implementation of the PCRE compiler and the regex runtime. 

To get the current lexer's driver, you can use the `getDriver()` method. And for
installation, you can use either the third argument of the Lexer's class
constructor, or install it forcibly using the `setDriver()` method.

```php
<?php

// Usage
$lexer = new \Phplrt\Lexer\Lexer($tokens, $skip, new MyDriver());

// Or alternative
$lexer->setDriver(new MyDriver());
```

## Markers Driver

The phplrt provides one driver implementation named `Markers`. This algorithm 
implements analysis and search of tokens based on 
[`*MARK:NAME` PCRE recorder](http://pcre.org/current/doc/html/pcre2pattern.html). 

For example, a token `\d+` named `T_DIGIT` will be compiled into approximately
the following regular expression: `/\G(?|(?:(?:\d+)(*MARK:T_DIGIT)))/`

## Custom Driver

To implement your own driver, you will need to implement an interface 
`Phplrt\Lexer\Driver\DriverInterface`:

```php
<?php

use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class MyDriver implements DriverInterface
{
    public function run(array $tokens, ReadableInterface $source, int $offset = 0): iterable
    {
        // The method accepts an array of tokens, an object with the source 
        // file and a shift in bytes relative to the beginning of the text 
        // from which you want to start parsing. 
    
        // The result should be a token iterator.
        yield new Token($tokenName, $tokenValue, $tokenOffset);
    }

    public function reset(): void 
    {
        //
        // Method for resetting internal memoized state, if required.
        //
        // For example, in cases where a set of tokens is copied to a whole 
        // regular expression after the first run.
        //
    }
}
```
