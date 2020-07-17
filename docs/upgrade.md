# Upgrade Guide

## Upgrading To 3.0 From 2.3

### PHP 7.4 Required

> Likelihood Of Impact: **Medium**

PHP 7.1 will no longer be actively maintained as of December 2019. Therefore, 
phplrt 3.0 requires PHP 7.4 or greater.

### Compilation To PHP Class Was Removed

> Likelihood Of Impact: **Medium**

The compiler code generation in the full-fledged PHP class has been removed 
and has been replaced by the generation of configs.

```php
$compiler = new \Phplrt\Compiler\Compiler();

/** @var \Phplrt\Compiler\Generator $assembly */
$assembly = $compiler->build();
```

- Method `Phplrt\Compiler\Generator::generateGrammar(string $class): string` 
    has been removed.
- Method `Phplrt\Compiler\Generator::generateBuilder(string $class): string` 
    has been removed.
- Method `Phplrt\Compiler\Generator::generate(string $class): string` has been 
    modified to `Phplrt\Compiler\Generator::generate(): string`.

#### Old 2.3.x Behaviour

```php
echo $assembly->generate('ClassName');
// class ClassName extends Parser 
// {
//    ...
// }
```

#### New 3.x Behaviour

```php
echo $assembly->generate();
// return [
//     'tokens' => [...],
//     'rules'  => [...],
//     ...etc
// ];
```

### Zend Generator Has Been Replaced By Laminas Generator

> Likelihood Of Impact: **Low**

The Zend project has been abandoned and is no longer supported: 
[https://getlaminas.org/blog/2019-12-31-out-with-the-old-in-with-the-new.html](https://getlaminas.org/blog/2019-12-31-out-with-the-old-in-with-the-new.html)

### Change the logic of the `prependMany` method

> Likelihood Of Impact: **Medium**

In the phplrt 2.3, calling the `prependMany()` method added tokens by default 
in the following order:

```php
$lexer->prependMany([
    'a' => 'a',
    'b' => 'b',
    'c' => 'c',
]);

// phplrt 2.3 lexer's data:
//  [
//      'c' => 'c',
//      'b' => 'b',
//      'a' => 'a',
//  ]

// New behavior:
//  [
//      'a' => 'a',
//      'b' => 'b',
//      'c' => 'c',
//  ]
```

In order to return to the old behavior, explicit transfer of the second 
argument is required:

```php
$lexer->prependMany([
    'a' => 'a',
    'b' => 'b',
    'c' => 'c',
], true);

// Expected token definitions:
//  [
//      'c' => 'c',
//      'b' => 'b',
//      'a' => 'a',
//  ]
```

