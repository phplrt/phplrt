# Source

> This package can be installed separately with `composer require phplrt/source`

Everything phplrt reads - a grammar file, an expression typed by a user, a
template - is wrapped in a *source* object. It is a thin thing: it knows how
to give up its content, and it knows what to call itself when an error points
at it.

```php
use Phplrt\Source\File;
use Phplrt\Source\Source;

$fromDisk   = new File(__DIR__ . '/example.txt');
$fromString = new Source('2 + 2');

echo $fromString->content; // "2 + 2"
```

## Why Not Just A String?

Two reasons.

The first is error messages. A parser that receives a bare string can only
say "syntax error at offset 42". A parser that receives a `File` can say:

```
 --> /app/config/routes.txt:7:12
```

The second is laziness. A `File` does not read the disk until somebody asks
for its content, and once it has, it remembers - until the file changes on
disk, at which point it reads again.

## The Kinds of Source

### File

A real file on disk.

```php
use Phplrt\Source\File;

$source = new File(__DIR__ . '/grammar.pp3');

echo $source->pathname;   // "/app/grammar.pp3"
echo $source->content;    // the contents of the file
echo $source->size;       // size in bytes
echo $source->modifiedAt; // unix timestamp

if ($source->isExists && $source->isReadable) {
    // ...
}
```

### Source

A string you already have in memory.

```php
use Phplrt\Source\Source;

$source = new Source('2 + 2');
```

### VirtualFile

A string that pretends to be a file. Nothing is read from disk, but errors
can still point at a name - handy for code that came from a database, an
HTTP request, or a test.

```php
use Phplrt\Source\VirtualFile;

$source = new VirtualFile('user-input.txt', '2 + 2');

echo $source->pathname; // "user-input.txt"
echo $source->content;  // "2 + 2"
```

### Stream

An open resource.

```php
use Phplrt\Source\Stream;
use Phplrt\Source\VirtualFileStream;

$source = new Stream(\fopen('php://input', 'rb'));

// ...or with a name attached
$named = new VirtualFileStream('request.json', \fopen('php://input', 'rb'));
```

## The Factory

If you do not know in advance what you are given, let the factory decide:

```php
use Phplrt\Source\SourceFactory;

$factory = new SourceFactory();

$factory->create('2 + 2');                        // Source
$factory->create(new \SplFileInfo('/app/x.txt')); // File
$factory->create(\fopen('php://memory', 'rb+'));  // Stream
```

There are explicit methods too, when you do want to be specific:

```php
$factory->createFromFile('/app/x.txt');
$factory->createFromString('2 + 2');
$factory->createFromString('2 + 2', 'virtual.txt'); // VirtualFile
$factory->createFromStream($resource);
$factory->createEmpty();
```

## The Interfaces

Type-hint against these rather than the concrete classes:

```php
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

// Anything readable: File, Source, VirtualFile, Stream...
function parse(ReadableInterface $source): mixed { /* ... */ }

// Only the ones that have a name: File, VirtualFile, VirtualFileStream
function report(FileInterface $source): string
{
    return $source->pathname;
}
```

`ReadableInterface` gives you two properties:

```php
$source->content; // string
$source->stream;  // resource
```

Both may throw `SourceExceptionInterface` - a file can disappear between the
moment you name it and the moment you read it.

```php
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Source\File;

try {
    echo new File('/no/such/file')
        ->content;
} catch (SourceExceptionInterface $e) {
    echo $e->getMessage(); // File "/no/such/file" not found
}
```

## Bring Your Own

`ReadableInterface` is small on purpose. If your source code lives somewhere
unusual - a zip archive, a remote service, a database row - implement it
yourself and every other phplrt component will accept it:

```php
use Phplrt\Contracts\Source\FileInterface;

final class DatabaseSource implements FileInterface
{
    public function __construct(
        public readonly string $pathname,
        private readonly \PDO $pdo,
        private readonly int $id,
    ) {}

    public string $content {
        get => $this->pdo
            ->query("SELECT body FROM templates WHERE id = {$this->id}")
            ->fetchColumn();
    }

    public mixed $stream {
        get {
            $stream = \fopen('php://memory', 'rb+');
            \fwrite($stream, $this->content);
            \rewind($stream);

            return $stream;
        }
    }
}
```
