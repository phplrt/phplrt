# Creating Project

This guide tells you how to write your own JSON parser from scratch. Although we
have already decided in advance on the grammar that we want to recognize, by
analogy with it, you can create any other implementations.

Before starting, you should create a new `composer.json` file and add the
necessary dependencies (see [installation](/docs/installation)) to it. As a
result, the file will look something like this:

```json
{
    "name": "app/json-parser",
    "require": {
        "php": "^7.4|^8.0",
        "phplrt/runtime": "^3.2"
    },
    "autoload": {
        "psr-4": {
            "JsonParser\\": "src"
        }
    },
    "require-dev": {
        "phplrt/phplrt": "^3.2"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

> Please note that the `composer.json` contains two packages: `phplrt/runtime`
> inside the `require` section, which provides the minimum assembly of components
> for the parser to work, and `phplrt/phplrt` inside the `require-dev` section,
> which contains all the necessary tools for development.

## Assembly Binary

Now we need to create a file that will allow us to process the source code of
the grammar and assemble it into a set of rules for the parser and lexer.

To do this, create a file, for example, `build.php` and write the following
code in it:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$compiler = new \Phplrt\Compiler\Compiler();

// Source grammar loading.
$compiler->load('

    // TODO

');

// Compiling the grammar into a set of
// instructions for the parser.
$assembly = $compiler->build();

// Saving an assembly to a file.
file_put_contents(__DIR__ . '/grammar.php', $assembly->generate());
```

> Please note that this code is for package development and will 
> not be needed in the future.

After running this code, a file `grammar.php` with the configuration 
of our future parser will appear.

## Creating Parser (Loading Grammar)

Let's now create a working parser code that will load this configuration. 
The first step is to load the lexer:

```php
<?php

declare(strict_types=1);

namespace JsonParser;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;

final class Parser
{
    private LexerInterface $lexer;

    public function __construct()
    {
        // This will be the path to our configuration file
        $config = require __DIR__ . '/grammar.php';

        $this->lexer = new Lexer(
            //
            // The "tokens" array field contains a list of lexer
            // states with list of regular expression of tokens.
            //
            // Since we use only one state, we can load the main one,
            // it is called "default" and is always available.
            //
            $config['tokens']['default'],
            //
            // This array field contains a list of token names
            // to skip.
            //
            $config['skip'],
        );
    }
}
```

Now the more difficult step is to load the parser. Let's modify the existing 
code and add it.

```php

// ...
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Parser as Runtime;

final class Parser
{
    private LexerInterface $lexer;
    private ParserInterface $parser;

    public function __construct()
    {
        $config = require __DIR__ . '/grammar.php';

        $this->lexer = /* ... lexer loading ... */
        
        $this->parser = new Runtime(
            //
            // The first required argument is a
            // reference to the lexer.
            //
            $this->lexer,
            //
            // The second argument is a list of parser rules.
            //
            // This list can also be loaded from a "grammar.php"
            // config file.
            //
            $config['grammar'],
            // Additional options
            [
                //
                // It is worth paying attention to one option, which
                // is also desirable to set: This is the name of the main
                // rule from which the analysis of grammar will begin.
                //
                Runtime::CONFIG_INITIAL_RULE => $config['initial'],
            ]
        );
    }
}

```

That's all! Now it remains to add a method for parsing!

```php
final class Parser
{
    /* ... constructor ... */ 

    public function parse(string $code): iterable
    {
        return $this->parser->parse($code);
    }
}
```

Now you can create a parser and use it. Of course it won't return any result
since we haven't written any rules. However, the main code is ready.

```php
$json = new JsonParser\Parser();

$ast = $json->parse('{"hello": 42}');

var_dump($ast);
```
