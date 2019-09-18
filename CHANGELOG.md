# Release Notes

## 2.1.0

- The [phplrt/compiler](https://github.com/phplrt/compiler) package was unarchived and actualized.
- The [phplrt/position](https://github.com/phplrt/position) package was added.
- The [phplrt/source](https://github.com/phplrt/source) package was added instead of old [phplrt/io](https://github.com/phplrt/io) (renaming).
- Added [phplrt/source-contracts](https://github.com/phplrt/source-contracts) interfaces.

#### Parser Contracts

- Interface `RuntimeExceptionInterface` was renamed to `ParserRuntimeExceptionInterface`.
- The `ParserRuntimeExceptionInterface::getNode(): NodeInterface` method added.
- Now `ParserInterface::parse(string|resource|ReadableInterface $source): iterable` allow 
`ReadableInterface` as a source argument. 
- Now parser should throw `ParserRuntimeExceptionInterface` instead `RuntimeExceptionInterface` 
when an error occurs during parsing. 

#### Lexer Contracts

- Interface `RuntimeExceptionInterface` was renamed to `LexerRuntimeExceptionInterface`.
- Now `LexerInterface::lex(string|resource|ReadableInterface $source, [int $offset = 0]): iterable` allow 
`ReadableInterface` as a source argument.
- Now parser should throw `LexerRuntimeExceptionInterface` instead `RuntimeExceptionInterface` 
when an error occurs during parsing. 

## 2.0.0

> **Please note that API is no longer compatible with 1.x**

- Fully rewritten [phplrt/lexer](https://github.com/phplrt/lexer).
    - Added multistate support.
    - More than **3 times** increased performance.
- Fully rewritten [phplrt/parser](https://github.com/phplrt/parser).
    - Changed algorithm from LL(k) [based on Hoa](https://github.com/Hoa/Compiler) to custom recursive\* and recurrent LL(k).
        > \* *may cause problems when working with XDebug.*
    - More than **36 times** increased performance.
    - More than **83 times** reduced peak memory consumption.
    - More than **4890 times** (sic!) reduced memory consumption.
- Added contracts support.
    - [Parser Contracts](https://github.com/phplrt/parser-contracts).
    - [Lexer Contracts](https://github.com/phplrt/lexer-contracts).
    - [AST Contracts](https://github.com/phplrt/ast-contracts).
- Added support for building AST using imperative code.
- Added support for the [PHP-Parser](https://github.com/nikic/PHP-Parser) architecture traverse.
    - *nikic:* [PhpParser\NodeTraverser](https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NodeTraverser.php).
    - *phplrt:* [Phplrt\Visitor\Traverser](https://github.com/phplrt/phplrt/blob/master/src/Visitor/Traverser.php).
- Deprecations:
    - The [phplrt/io](https://github.com/phplrt/io) package was deprecated.
    - The [phplrt/stream](https://github.com/phplrt/stream) package was deprecated.
    - The [phplrt/exception](https://github.com/phplrt/exception) package was deprecated.
- Temporary deprecations:
    - The [phplrt/ast](https://github.com/phplrt/ast) package was temporarily archived.
    - The [phplrt/compiler](https://github.com/phplrt/compiler) package was temporarily archived.
- *[...and much more](https://github.com/phplrt/phplrt/blob/master/README.md)*.

## 1.1.0

- Method `Readable::getStreamContents()` was deprecated.
- The stream package (PSR-7 based) was added.
- Method `Readable::getStream(): StreamInterface` was added.

## 1.0.2

- Fix `Exception::throwsIn` method (remove clone operation).

## 1.0.1

- Allow previous exception overriding using method `Exception::from($exception, $previous)`
- Fix `Exception::from` method (remove clone operation)

## 1.0.0

- Initial release
