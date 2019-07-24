# Assembler

The assembler library allows you to collect a set of classes and 
files into a separate unit of execution (bundle/package/assembly/etc.).

## Building

```php
<?php
use Phplrt\Assembler\Assembler;

(new Assembler())
    ->build(MyClass::class)
    ->save(__DIR__ . '/dir', 'file');
```

### Building With Dependencies

```php
<?php
use Phplrt\Assembler\Assembler;
use Phplrt\Assembler\Loader\MatcherInterface;

(new Assembler())
    ->with(Example\Dependency::class, static function (MatcherInterface $matcher) {
        $matcher->namespaced('Example');
    })
    ->build(MyClass::class, static function (MatcherInterface $matcher) {
        $matcher->namespaced('App');
    })
    ->save(__DIR__ . '/dir', 'file');
``` 
