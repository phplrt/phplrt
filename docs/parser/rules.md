# Rules

In addition, there are other grammar rules. Note that each of the rules 
contains a `reduce()` method.

Each terminal rule contains the following method signature:
- `reduce(BufferInterface $buffer): ?TokenInterface;`

Each non-terminal rule contains the following method signature:
- `reduce(BufferInterface $buffer, \Closure $next): mixed;`

## Lexeme

Refers to the token defined in the lexer.

```php
<?php
use Phplrt\Parser\Grammar\Lexeme;

$kept = new Lexeme('T_NUMBER');
```

![/img/docs/rule-lexeme.png](/img/docs/rule-lexeme.png)

The picture shows the scheme of work of this rule. Let's now create this buffer
on which we will further check the rules:

```php
<?php

use Phplrt\Buffer\ArrayBuffer;use Phplrt\Lexer\Token\Token;

$buffer = new ArrayBuffer([
    new Token('T_DIGIT', '2', 0),
    new Token('T_PLUS', '+', 2),
    new Token('T_DIGIT', '2', 4),
]);
```

And let's try to reproduce its work:

```php
<?php

$rule = new \Phplrt\Parser\Grammar\Lexeme('T_PLUS');

while ($buffer->valid()) {
    var_dump($buffer->key(), $rule->reduce($buffer));

    $buffer->next();
}

//
// Approximate Output:
//
// int(0)   NULL
//
// int(1)   object(Phplrt\Lexer\Token\Token)#7 (4) {
//              ["offset":private] => int(2)
//              ["value":private]  => string(1) "+"
//              ["name":private]   => string(6) "T_PLUS"
//          }
//
// int(2)   NULL
//
```

This rule contains an additional Boolean option (second argument), which 
indicates that this token will be visible as one of the `$children` of 
the [AST builder](/docs/parser/ast) methods.

```php
<?php
use Phplrt\Parser\Grammar\Lexeme;

$skipped = new Lexeme('T_WHITESPACE', false);
```

## Concatenation 

Sequence of rules.

```php
<?php
use Phplrt\Parser\Grammar\Concatenation;

//
// EBNF: 
//  concat = some1 any2 rule3;
//
new Concatenation([<ID_1>, <ID_2>, <ID_3>]);
```

## Alternation 

Choosing between several rules.

```php
<?php
use Phplrt\Parser\Grammar\Alternation;

//
// EBNF: 
//  choice = some1 | any2 ;
//
new Alternation([<ID_1>, <ID_1>]);
```

## Repetition

Repeat one or more rules.

```php
<?php
use Phplrt\Parser\Grammar\Repetition;

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
use Phplrt\Parser\Grammar\Optional;

//
// EBNF:
//  optional = some? ;
//

$optional = new Optional(<ID_1>);

// Same as "new Repetition(<ID_1>, 0, 1)", but faster
```
