# Rules

In addition, there are other grammar rules.

## Alternation 

Choosing between several rules.

```php
<?php
use Phplrt\Parser\Rule\Alternation;

//
// EBNF: 
//  choice = some1 | any2 ;
//
new Alternation(<ID>, [<ID_1>, <ID_1>], 'choice');
```

## Concatenation 

Sequence of rules.

```php
<?php
use Phplrt\Parser\Rule\Concatenation;

//
// EBNF: 
//  concat = some1 any2 rule3;
//
new Concatenation(<ID>, [<ID_1>, <ID_2>, <ID_3>], 'concat');
```

## Repetition

Repeat one or more rules.

```php
<?php
use Phplrt\Parser\Rule\Repetition;

//
// EBNF:
//  repeat_zero_or_more = some* ;
//
new Repetition(<ID>, 0, -1, <ID_1>, 'repeat_zero_or_more');

//
// EBNF: 
//  repeat_one_or_more = some+ ;
//
new Repetition(<ID>, 1, -1, <ID_2>, 'repeat one or more');
```

## Terminal

Refers to the token defined in the lexer.

```php
<?php
use Phplrt\Parser\Rule\Terminal;

$kept = new Terminal(<ID>, 'T_NUMBER', true);

$skipped = new Terminal(<ID>, 'T_WHITESPACE', false);
```
