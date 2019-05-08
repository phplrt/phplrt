# Compiler

This is the implementation of the so-called compiler-compiler based on 
the basic capabilities of [Hoa\Compiler](https://github.com/hoaproject/Compiler).

The library is needed to create parsers from grammar files and is not used 
during the parsing itself, this is only required for development.

Before you begin to work with custom implementations of parsers, it is 
recommended that you review the [EBNF](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form)

## Loading

You can immediately load the grammar into memory and execute it 
using method `$compiler->parse(...)`:

```php
<?php
use Phplrt\Io\File;
use Phplrt\Compiler\Compiler;

$compiler = Compiler::load(
    $grammar = File::fromPatname(__DIR__ . '/path/to/file.pp2')
);

$ast = $compiler->parse(
    $sources = File::fromSources('example text')
);

echo $ast;
```

## Compilation

Reading a grammar is quite simple operation, but it still takes time 
to execute. After the grammar rules have been formulated, you can "fix" the version 
in a separate parser class that will contain all the logic and no longer require 
reading the source code. After you compile it into a class, this package (phplrt/compiler) 
can be excluded from composer dependencies.

```php
$compiler = Compiler::load(File::fromPathname('path/to/grammar.pp2'));

$compiler->setNamespace('Example')
    ->setClassName('Parser')
    ->saveTo(__DIR__);
```

This code example will create a parser class in the current directory 
with the required class and namespace names. An example of the result of generation 
can be found [in an existing project here](https://github.com/phplrt/phplrt/blob/master/src/Compiler/Grammar/Parser.php).
As a source, [this grammar file](https://github.com/phplrt/phplrt/blob/master/src/Compiler/Resources/pp2/grammar.pp2). 
