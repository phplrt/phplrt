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
new Alternation([<ID_1>, <ID_1>]);
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
new Concatenation([<ID_1>, <ID_2>, <ID_3>]);
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
new Repetition(<ID_1>, 0, \INF); // repeat rule #1 from 0 to inf

//
// EBNF: 
//  repeat_one_or_more = some+ ;
//
new Repetition(<ID_2>, 1, \INF); // repeat rule #2 from 1 to inf

//
// EBNF: 
//  repeat_1_2_or_3_times = some{1,3} ;
//
new Repetition(<ID_3>, 1, 3); // repeat rule #3 from 1 to 3
```

## Optional

Optional rule

```php
<?php
use Phplrt\Parser\Rule\Optional;

//
// EBNF:
//  optional = some? ;
//

$optional = new Optional(<ID_1>);

// Same as "new Repetition(<ID_1>, 0, 1)", but faster
```

## Lexeme

Refers to the token defined in the lexer.

```php
<?php
use Phplrt\Parser\Rule\Lexeme;

$kept = new Lexeme('T_NUMBER', true);

$skipped = new Lexeme('T_WHITESPACE', false);
```
