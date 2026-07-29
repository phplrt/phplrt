# Error Reporting

> This package can be installed separately with
> `composer require phplrt/exception`

An error that says "syntax error at offset 137" is technically correct and
practically useless. Phplrt errors point at the source:

```
error[UnexpectedTokenException]: Syntax error, unexpected "3" (T_NUMBER)
 --> expr.txt:2:1
  |
1 | 1 + 2
2 | 3 * (4 + )
  | ^
3 |
```

This works out of the box: install `phplrt/exception` and any parser or lexer
exception renders like this when converted to a string.

## Catching Syntax Errors

```php
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Source\VirtualFile;

try {
    $parser->parse(new VirtualFile('expr.txt', $input));
} catch (UnexpectedTokenException $e) {
    echo $e->getMessage(); // Syntax error, unexpected "3" (T_NUMBER)
    echo $e;               // ...plus the snippet above
}
```

The exception carries everything needed to build your own message:

```php
$e->token;         // the token it choked on
$e->token->name;   // T_NUMBER
$e->token->offset; // 6
$e->source;        // the source it was reading
```

## Give Your Sources A Name

This is the one thing you have to do yourself. A bare `Source` has no name,
so an error can only show the snippet:

```php
$parser->parse(new Source($input));
```

```
error[UnexpectedTokenException]: Syntax error, unexpected "3" (T_NUMBER)
  |
1 | 3
  | ^
```

Wrap the input in a `File` or a `VirtualFile` and the error can say *where*:

```php
$parser->parse(new VirtualFile('user-input.txt', $input));
```

```
 --> user-input.txt:2:1
```

`VirtualFile` costs nothing - it is a string with a name attached - so use it
even when the input never touched the disk.

## Catching Everything

The contracts give you one interface per stage, which is usually the right
granularity:

```php
use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;

try {
    $parser->parse($source);
} catch (SourceExceptionInterface $e) {
    // The source could not be read at all
} catch (LexerExceptionInterface $e) {
    // The text could not be turned into tokens
} catch (ParserExceptionInterface $e) {
    // The tokens did not match the grammar
}
```

Each has a `RuntimeExceptionInterface` counterpart for errors that happened
while reading a *particular* source, as opposed to errors in the setup:

```php
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

catch (RuntimeExceptionInterface $e) {
    // Something in the input, not something in the grammar
}
```

## Errors Of Your Own

Parsing is rarely the last step. The stages after it - resolving names, type
checking, evaluating - find their own problems, and those deserve the same
treatment. `ErrorPrinter` renders any offset in any source:

```php
use Phplrt\Exception\ErrorPrinter;
use Phplrt\Source\VirtualFile;

$source = new VirtualFile('config.txt', <<<'TXT'
    name = "phplrt"
    version = four
    debug = true
    TXT);

echo new ErrorPrinter()
    ->print($source, offset: 26, length: 4)
    ->withMessage('Expected a number')
    ->withClass('TypeError');
```

```
error[TypeError]: Expected a number
 --> config.txt:2:11
  |
1 | name = "phplrt"
2 | version = four
  |           ^^^^
3 | debug = true
```

This is where the `$offset` you kept on every AST node pays for itself.

### Severity

```php
use Phplrt\Exception\Printer\Level;

echo new ErrorPrinter()
    ->print($source, 26, 4)
    ->withMessage('Consider writing 4 instead')
    ->withLevel(Level::Warning);
```

```
warning: Consider writing 4 instead
 --> config.txt:2:11
  |
1 | name = "phplrt"
2 | version = four
  |           ^^^^
3 | debug = true
```

`Level::Error`, `Level::Warning` and `Level::Debug` are available.

### Adjusting The Output

Everything is a `with*()` method returning a new object, and the source is
read only when the result is turned into a string:

```php
$printer->print($source, $offset, $length)
    ->withMessage('...')       // the message above the snippet
    ->withClass('MyError')     // the name in brackets after the level
    ->withLevel(Level::Warning)
    ->withPathname('other.txt') // override the file name
    ->withLinesAround(0)        // no context lines, just the one that matters
    ->withLength(4);            // the size of the underlined fragment
```

`withLinesAround(0)` is worth knowing about - for a list of many warnings,
two context lines each is a wall of text.

### Colors

Output to a terminal is colored, and output to anything else is not.
Override that when you need to:

```php
use Phplrt\Exception\Printer\RustStylePrinter;

$printer = new ErrorPrinter(new RustStylePrinter(
    colors: false,
));

// ...or set the width, for a narrow terminal
$printer = new ErrorPrinter(new RustStylePrinter(
    width: 80,
));
```

### A Different Format

`PrinterInterface` takes the captured lines and returns a string, so a
one-line-per-error format for a CI log is a small class:

```php
use Phplrt\Exception\Printer\ErrorInfo;
use Phplrt\Exception\Printer\PrinterInterface;
use Phplrt\Exception\Snippet\CapturedSourceLine;

final class CompactPrinter implements PrinterInterface
{
    public function print(iterable $snippets, ?ErrorInfo $info = null): string
    {
        foreach ($snippets as $line) {
            // Only the lines containing the error itself are captured
            if ($line instanceof CapturedSourceLine) {
                return \sprintf(
                    '%s:%d:%d: %s',
                    $info?->pathname ?? '-',
                    $line->number,
                    $line->startColumn,
                    $info?->message ?? '',
                );
            }
        }

        return $info?->message ?? '';
    }
}
```

```php
$printer = new ErrorPrinter(new CompactPrinter());

echo $printer->print($source, 26, 4)
    ->withMessage('Expected a number');
// config.txt:2:11: Expected a number
```

## Attaching Snippets To Your Own Exceptions

The pattern the parser uses works for anything: keep the source and the
offset on the exception, and render them in `__toString()`.

```php
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\ErrorPrinter;

final class TypeException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ReadableInterface $source,
        public readonly int $offset,
        public readonly int $length = 0,
    ) {
        parent::__construct($message);
    }

    public function __toString(): string
    {
        return (string) new ErrorPrinter()
            ->print($this->source, $this->offset, $this->length)
            ->withMessage($this->getMessage())
            ->withClass(static::class);
    }
}
```

Now every error your language reports looks the same as every error phplrt
reports, which is exactly what you want.
