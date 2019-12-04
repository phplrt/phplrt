# Compiler

The library is needed to create parsers from grammar files and is not used 
during the parsing itself, this is only required for development.

Before you begin to work with custom implementations of parsers, it is 
recommended that you review the [EBNF](https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form)

## Loading

You can immediately load the grammar into memory and execute it 
using method `$compiler->parse(...)`:

```php
<?php
use Phplrt\Source\File;
use Phplrt\Compiler\Compiler;

$compiler = new Compiler();
$compiler->load(File::fromPatname(__DIR__ . '/path/to/file.pp2'));

echo $compiler->parse(File::fromSources('example text'));
```

## Compilation

Reading a grammar is quite simple operation, but it still takes time 
to execute. After the grammar rules have been formulated, you can "fix" the version 
in a separate parser class that will contain all the logic and no longer require 
reading the source code. After you compile it into a class, this package (phplrt/compiler) 
can be excluded from composer dependencies.

```php
use Phplrt\Source\File;
use Phplrt\Compiler\Compiler;

$compiler = (new Compiler())
    ->load(File::fromPathname('path/to/grammar.pp2'))
    ->build('App\\ExpressionParser')
    ->save(__DIR__ . '/ExpressionParser.php')
;
```

This code example will create a parser class in the current directory 
with the required class and namespace names. An example of the result of generation 
can be found [in an existing project here](https://github.com/phplrt/phplrt/blob/master/src/Compiler/src/Grammar/PP2Grammar.php).
