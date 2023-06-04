# Creating Project

This guide tells you how to write your own addition expression parser from
scratch. Although we have already decided in advance on the grammar that we
want to recognize, by analogy with it, you can create any other
implementations.

> The full result of this code can be [downloaded from here](/downloads/simple-math-project.zip).

Before starting, you should create a new `composer.json` file and add the
necessary dependencies (see [installation](/docs/installation)) to it. As a
result, the file will look something like this:

```json
{
    "name": "app/calculator",
    "require": {
        "php": "^7.4|^8.0",
        "phplrt/runtime": "^3.2"
    },
    "autoload": {
        "psr-4": {
            "Calculator\\": "src"
        }
    },
    "require-dev": {
        "phplrt/phplrt": "^3.2"
    }
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

> More information about the assembly is written in the
> [corresponding page](/docs/compiler).

## Creating Parser

Let's now create a working parser code that will load this configuration.
The first step is to load the lexer:

```php
<?php

declare(strict_types=1);

namespace Calculator;

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

> More information about how the lexer works and works is written
> in [this page](/docs/lexer).

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

> More information about how the parser works and works is written
> in [this page](/docs/parser).

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
$math = new Calculator\Parser();

$ast = $math->parse('2 + 2');

var_dump($ast);
```

## Writing Grammar

With the foundation in place, we can now start writing the grammar. You can
read more about all its features on the
[grammar documentation page](/compiler/grammar), but here we will focus on the
simplest. To do this, open our first `build.php` assembly file and write the
following:

```php
// Source grammar loading.
$compiler->load('

    // All digits sequence should be recognized as "number"
    %token number \d+
    
    // All "+" chars should be recognized as "plus"
    %token plus \+

    // All whitespace chars should be ignored
    %skip whitespace \s+

    // This means that each "expression" matches the sequence:
    //  - "number" (required)
    //  - then optional (from 0 to 1):
    //      - "plus"
    //      - then another "expression"
    expression : number() (::plus:: expression())?

    number : <number>

');
```

Don't forget to call the `build.php` script again to generate a new
`grammar.php` file. After the `grammar.php` file is generated, we can run
the `2 + 2` expression recognition test again.

```php
$ast = $math->parse('2 + 2 + 4');

var_dump($ast);
```

As a result, this code should output the following result:
```php
array:3 [
  0 => Phplrt\Lexer\Token\Token {
    -bytes: null
    -offset: 0
    -value: "2"
    -name: "number"
  }
  1 => Phplrt\Lexer\Token\Token {
    -bytes: null
    -offset: 4
    -value: "2"
    -name: "number"
  }
  2 => Phplrt\Lexer\Token\Token {
    -bytes: null
    -offset: 8
    -value: "4"
    -name: "number"
  }
]
```

> Don't worry about the `-bytes: null` value being `null` on output, it will
> be initialized when this data is read.

As a result, you will get back a list of tokens that you marked as "keep" using
`<token-name>` syntax (instead of `::token-name::`).

In the case that you try to recognize an incorrect expression, you will receive
an error message.

```php
$math->parse('2 + 2 & 4');

// Uncaught Phplrt\Lexer\Exception\UnrecognizedTokenException: Syntax error, unrecognized "&"
//    1. | 2 + 2 & 4
//       |       ^ in /.../lexer/src/Exception/UnrecognizedTokenException.php:30

$math->parse('2 + ');

// Uncaught Phplrt\Parser\Exception\UnexpectedTokenWithHintsException: Unexpected end of code. 
//    1. | 2 + 
//       |      in /.../parser/src/Exception/UnexpectedTokenWithHintsException.php:40

$math->parse('+ 42');

// Uncaught Phplrt\Parser\Exception\UnexpectedTokenWithHintsException: Syntax error, unexpected "+" (plus). 
//    1. | + 42
//       | ^ in /.../parser/src/Exception/UnexpectedTokenWithHintsException.php:40
```

## Building AST

After we have written the parser, we can transform the set of expressions into
their corresponding abstract syntax tree.

A builder is used for this, which can be replaced in the future, but for now we
will use the standard one. To do this, open the `Parser.php` file and add a new
`CONFIG_AST_BUILDER` option to it.

```php
// ...
use Phplrt\Parser\SimpleBuilder;

final class Parser
{
    /* ... */

    public function __construct()
    {
        /* ... lexer initialization ... */

        $this->parser = new Runtime($this->lexer, $config['grammar'], [
            /** ... other options ... */
            
            //
            // This option contains a reference to the tree builder instance.
            //
            // All construction rules will be loaded from the "reducers" section
            // of the compiled grammar.
            //
            Runtime::CONFIG_AST_BUILDER => new SimpleBuilder($config['reducers']),
        ]);
    }
}
```

After that, two node classes should be created: The first one will be
responsible for the literals (number).

```php
<?php

declare(strict_types=1);

namespace Calculator\Node;

use Phplrt\Contracts\Ast\NodeInterface;

final class Number implements NodeInterface
{
    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
```

And the second one for the sum expression.

```php
namespace Calculator\Node;

use Phplrt\Contracts\Ast\NodeInterface;

final class Addition implements NodeInterface
{
    public Number $a;
    public object $b;

    /** @param Number|Addition $b */
    public function __construct(Number $a, object $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
```

After the classes have been created, the rules for their construction should
be written. To do this, open the grammar file (`build.php`) and modify it.


```php
$compiler->load('
    // ... token definitions ...

    /**
     * BEFORE:
     *
     *  expression : number() (::plus:: expression())?
     *
     *  number : <number>
     *
     */

    expression -> {
        // in case of "$children" sequence is a "number()" and "expression()"
        if (count($children) === 2) {
            return new \Calculator\Node\Addition($children[0], $children[1]);
        }

        // otherwise (only "number()")
        return $children;
    }
      : number() (::plus:: expression())?

    number -> { return new \Calculator\Node\Number((int)$token->getValue()); }
      : <number>

');
```

Let's call the code:

```php
// Execution "2 + 2 + 4" expression:

$ast = $math->parse('2 + 2 + 4');

var_dump($ast);
```

```php
// Expected Output:

Calculator\Node\Addition {
  +a: Calculator\Node\Number {
    +value: 2
  }
  +b: Calculator\Node\Addition {
    +a: Calculator\Node\Number {
      +value: 2
    }
    +b: Calculator\Node\Number {
      +value: 4
    }
  }
}
```
