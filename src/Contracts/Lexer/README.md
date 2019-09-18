<p align="center">
    <a href="https://railt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>

## Lexer Contracts

A set of interfaces for abstraction over lexers.

The main lexer interface is described in a single method `lex` which 
can receive an arbitrary data type as a source and return a list of tokens:

- `public function lex(string|resource $source[, int $offset = 0]): iterable`

Although the method `lex($source)` allows to pass anything, it is obliged to 
guarantee the work with the `resource` and `string` types.

Such "weakening" are made in order to facilitate portability 
between different implementations which require specific data types 
(like `Source` class in [Hoa](https://github.com/hoaproject/Compiler)) but 
definitely guarantee the work with resource streams (lazy implementations), 
or with source texts (eager implementations).

## Exceptions

In the event that an error occurred during initialization, an exception 
of type `Phplrt\Contracts\Lexer\LexerExceptionInterface` should be thrown. This 
exception indicates any type of internal error (for example, misspelled PCRE).

Please note that argument mismatch with accepted types allows 
`TypeError`, `AssertionError` or `InvalidArgumentException` exceptions.

#### Runtime Exceptions

In the case that an error occurred during execution, an exception of type 
`Phplrt\Contracts\Lexer\RuntimeExceptionInterface` should be thrown. In 
addition to being a descendant of the `LexerExceptionInterface`, it contains a 
`getToken(): TokenInterface` method that returns the token on which the 
error occurred.

## Tokens

The `lex()` method can return any iterator that contains a list of 
token implementations. Each token must be defined by an interface:

```php
namespace Phplrt\Contracts\Lexer;

interface TokenInterface 
{
    public function getName(): string;
    public function getOffset(): int;
    public function getValue(): string;
    public function getBytes(): int;
}
```

#### Name

The `getName()` method returns a name identifier to which you can refer.

```php
foreach ($tokens as $token) {
    echo 'Token name is: ' . $token->getName() . "\n";
}
```

#### Offset

The `getOffset()` method returns the shift in **bytes** (not chars) relative to 
the beginning of the source data. It is mainly needed for debugging, when want 
to report where the error occurred.

In addition, the offset relative to the beginning of the source data can be 
quite simply converted to a line and offset (relative to the line), but it 
takes up less space (one number, instead of two).

#### Value

The `getValue()` method returns token string data. Using a string value
facilitates portability. 

In the case that the token is composite, the use of another method is 
required, for example:

```php
class Token implements TokenInterface
{
    public function getChildren(): iterable
    {
        // ...
    }
}
```

*Note: Composite tokens can be used to describe complex structures like strings 
that contain interpolated expressions and special chars.*

#### Bytes

The `getBytes()` method returns token length in bytes. This information is 
often required to implement multistate lexers.

## Constants

Each token contains a set of constants that define some standard categories 
and are used instead of identifiers, i.e. will returned by the `getName` method.

#### End Of Input

The `TokenInterface::END_OF_INPUT` constant contains an ID of a token that 
marks the end of the incoming data.

The char `\0` can be a value of such a token.
