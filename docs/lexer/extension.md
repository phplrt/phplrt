# Lexer Extension

When the lexer is already created, sometimes it may be necessary to modify it.
Phplrt provides an imperative interface for controlling the behavior of the 
lexer after it instantiating.

## Adding New Tokens

It is worth remembering that the order of determining tokens in a lexer 
affects its logic. Therefore, to add new tokens, there are methods for 
adding a new token to the beginning (`prepend`) or end (`append`) of the 
token definitions list.

### Append

```php {highlight: [5]}
<?php

$lexer = new \Phplrt\Lexer\Lexer(['T_INT' => '\d+']);

$lexer->append('T_FLOAT', '\d+\.\d+');

// Expected Token Definitions:
// [
//     'T_INT' => '\d+',
//     'T_FLOAT' => '\d+\.\d+',
// ]
```

This is incorrect behavior because any numbers will be 
determined primarily as `T_INT`.

```php {highlight: ['11-13']}
foreach ($lexer->lex('42.0') as $i => $token) {
    echo $i . ' => ' . $token . "\n";
}

//
// Expected Output:
//
// 0 => "42" (T_INT)
// 1 => Error

 Syntax error, unrecognized "."
  1. | 42.0
     |   ^ in .../Lexer/src/Exception/UnrecognizedTokenException.php:40
```

### Prepend

In this case, we needed to use the `prepend` method.

```php {highlight: [5]}
<?php

$lexer = new \Phplrt\Lexer\Lexer(['T_INT' => '\d+']);

$lexer->prepend('T_FLOAT', '\d+\.\d+');

// Expected Token Definitions:
// [
//     'T_FLOAT' => '\d+\.\d+',
//     'T_INT' => '\d+',
// ]
```

```php 
foreach ($lexer->lex('42.0') as $i => $token) {
    echo $i . ' => ' . $token . "\n";
}

//
// Expected Output:
//
// 0 => "42.0" (T_FLOAT)
// 1 => \0
//
```

### Append Many

In the case when you need to add several new tokens to the end, you can 
use the `appendMany` method.

```php {highlight: ['4-5']}
$lexer = new \Phplrt\Lexer\Lexer(['T_WHITESPACE' => '\s+']);

$lexer->appendMany([
    'T_FLOAT' => '\d+\.\d+',
    'T_INT'   => '\d+',
]);

// Expected Token Definitions:
// [
//     'T_WHITESPACE' => '\s+',
//     'T_FLOAT' => '\d+\.\d+',
//     'T_INT' => '\d+',
// ]
```

### Prepend Many

In the case when you need to add several new tokens to the end, you can 
use the `appendMany` method.

```php {highlight: ['4-5']}
$lexer = new \Phplrt\Lexer\Lexer(['T_WHITESPACE' => '\s+']);

$lexer->prependMany([
    'T_INT'   => '\d+',
    'T_FLOAT' => '\d+\.\d+',
]);

// Expected Token Definitions:
// [
//     'T_INT' => '\d+',
//     'T_FLOAT' => '\d+\.\d+',
//     'T_WHITESPACE' => '\s+',
// ]
```

Please note that the entire list was added to the beginning, and **not each** 
of the tokens. In case for each of the transferred tokens to be added to the 
beginning, the second argument of the `prependMany` method should be specified

```php {highlight: ['4-5']}
$lexer = new \Phplrt\Lexer\Lexer(['T_WHITESPACE' => '\s+']);

$lexer->prependMany([
    'T_INT'   => '\d+',
    'T_FLOAT' => '\d+\.\d+',
]);

// Expected Token Definitions:
// [
//     'T_FLOAT' => '\d+\.\d+',
//     'T_INT' => '\d+',
//     'T_WHITESPACE' => '\s+',
// ]
```

## Removing Tokens

In addition to adding tokens, it is possible to delete old.

### Remove

In case to delete a previously registered token, you need to call the 
`remove(string ...$tokens)` method.

```php {highlight: ['2-4', 7]}
$lexer = new \Phplrt\Lexer\Lexer([
    'T_WHITESPACE' => '\s+',
    'T_FLOAT'      => '\d+\.\d+',
    'T_INT'        => '\d+',
]);

$lexer->remove('T_FLOAT', 'T_INT');
```

`T_FLOAT` and `T_INT` tokens will be removed from the lexer and as a result 
will remain there.

```php
[
    'T_WHITESPACE' => '\s+',
]
```
