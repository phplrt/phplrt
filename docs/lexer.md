# Lexer

Lexical analysis, lexing or tokenization is the process of converting a 
sequence of characters into a sequence of tokens (strings with an assigned 
and thus identified meaning).
 
A program that performs lexical analysis may be termed a lexer, 
tokenizer, or scanner, though scanner is also a term for the first stage of a 
lexer. A lexer is generally combined with a parser, which together analyze 
the syntax of programming languages, web pages, and so forth.

In order to familiarize ourselves with the concepts of lexical analysis in more 
detail, we can use [this link here](https://www.google.com/?q=lexical%20analysis), 
but now we will stop on an examples.

Let's try to recognize this expression: `2 + 2`

### Single State

```php
<?php

$lexer = Phplrt\Lexer\Lexer::create([
    // Note that #0 reserved for T_EOI token (end of input).
    1 => '\h+', // whitespaces
    2 => '\n',  // new lines
    3 => '\d+', // digits
    4 => '\+',  // symbol "+"
]);

foreach ($lexer->lex('2 + 2') as $token) {
    echo $token->getType() . ' : ' . $token . "\n";
}
//
// Expected Output:
// > 3 : "2"
// > 1 : " "
// > 4 : "+"
// > 1 : " "
// > 3 : "2"
// > 0 : "\0"
//
```

### Skipping

```php
<?php
$lexer = Phplrt\Lexer\Lexer::create([  
    1 => '\h+', // whitespaces
    2 => '\n',  // new lines
    3 => '\d+', // digits
    4 => '\+',  // symbol "+"
],
[
    1,  // whitespaces should be skipped 
    2   // new lines should be skipped too
]);

foreach ($lexer->lex('2 + 2') as $token) {
    echo $token->getType() . ' : ' . $token . "\n";
}

//
// Expected Output:
// > 3  : "2"
// > -1 : " "
// > 4  : "+"
// > -1 : " "
// > 3  : "2"
// > 0  : "\0"
//
```

### Multistates

```php
$lexer = new Phplrt\Lexer\Lexer([
    // State 0 (expressions)
    0 => [
        [
            1 => '\h+',   // whitespaces
            2 => '\n',    // new lines
            3 => '\d+',   // digits
            4 => '\+',    // symbol "+"
            5 => '/\*\*'  // comment start
        ],
        [
            1,
            2
        ],
        [
            5 => 1 
            // When token #5 (comment start) is matched, 
            // we will go to state 1 (i.e. open the comment).
        ]
    ],
    // State 1 (comments)
    1 => [
        [
            1 => '\*/', // comment end
            2 => '.*?'  // any text
        ],
        [],
        [
            1 => 0
            // When token #1 (comment end) is matched, 
            // we will go to state 0 (i.e. close the comment).
        ]
    ]
]);
```

### Custom States

```php
<?php
$lexer = new Phplrt\Lexer\Lexer([
    // Should be instance StateInterface
    new MyCustomState(),
    new MyCustomState2(),
]);
```

### Multistate Lookaheads

Transition to different states with lookahead

```php
<?php
$lexer = new Phplrt\Lexer\Lexer([
    0 => [
        [
            1 => '<\?php',
            // ... other tokens
        ],
        [
            // skips
        ],
        [
            // transitions
        ],
        [
            1 => 0,
            // Before the token #1 (a "<?php" tag) is matched, 
            // we will go to state 1 (i.e. php lexer).
        ]
    ],
    1 => new PhpLexer() // e.g. "token_get_all()" implementation
]);
```

### Redistributable

```php
<?php

class MathAdditionLexer extends \Phplrt\Lexer\AbstractLexer
{
    protected $states = [
        0 => [
            [
                1 => '\h+', // whitespaces
                2 => '\n',  // new lines
                3 => '\d+', // digits
                4 => '\+',  // symbol "+"
            ]   
        ]   
    ];
}

$lexer = new MathAdditionLexer();

foreach ($lexer->lex('2 + 2') as $token) {
    echo $token->getType() . ' : ' . $token . "\n";
}
```
