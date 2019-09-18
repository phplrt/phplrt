<p align="center">
    <a href="https://railt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>

## Parser Contracts

A set of interfaces for abstraction over parsers.

The main parser interface is described in a single method `parse($source)` 
which can receive an arbitrary data type as a source and return a list of 
[AST nodes](https://github.com/phplrt/ast-contracts).

```php
namespace Phplrt\Contracts\Parser;

interface ParserInterface
{
    public function parse($source): iterable;
}
```

In the case that an error occurred in parser initialization, it should 
throw a general `Phplrt\Contracts\Parser\ParserExceptionInterface` exception.

In case an exception occurred while the parser is running, a 
`Phplrt\Contracts\Parser\RuntimeExceptionInterface` error should be raised.
