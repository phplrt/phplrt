# Grammar Rules

A grammar is a flat array of rules. Each rule sits at an index, and rules
refer to each other by that index:

```php
$grammar = [
    0 => new Concatenation([1, 2]), // "read rule #1, then rule #2"
    1 => new Lexeme(tokenId: 0),
    2 => new Lexeme(tokenId: 1),
];
```

There are five rule classes, and between them they cover everything EBNF can
say. You will usually get them from a
[grammar file](/docs/compiler/grammar) or
[the builder](/docs/parser/builder), but it helps to know what each one does.

## Lexeme

Reads a single token. This is where a grammar touches the actual input -
every other rule is written in terms of other rules.

```php
use Phplrt\Parser\Grammar\Lexeme;

new Lexeme(tokenId: 0);              // read T_DIGIT, keep it
new Lexeme(tokenId: 1, keep: false); // read T_COMMA, throw it away
```

```pp2
Rule : <T_DIGIT> ;    // keep
Rule : ::T_COMMA:: ;  // read and discard
```

`keep: false` is for punctuation. A comma between list items has to be
*there*, but nobody needs it in the result - dropping it early means you do
not have to filter it out later.

Note the token is addressed by **id**, not by name. What it looks like in the
source is the lexer's business.

## Concatenation

Reads several rules, one after another. All of them must match, in order.

```php
use Phplrt\Parser\Grammar\Concatenation;

new Concatenation([1, 2, 1]);
```

```pp2
Rule : Number() Plus() Number() ;
```

If any of them fails, the whole sequence fails: the input rewinds to where
the sequence started and everything read along the way is dropped.

## Alternation

Tries the rules in order and takes the first one that matches.

```php
use Phplrt\Parser\Grammar\Alternation;

new Alternation([1, 2]);
```

```pp2
Rule : Number() | Name() ;
```

**The order is part of the meaning.** The first match wins, and the rest are
never tried - even if one of them would have read more of the input:

```pp2
Rule : "a" | "ab" ;   // never reads "ab"
Rule : "ab" | "a" ;   // ✔
```

This is what makes a PEG grammar unambiguous. If you are coming from a
classic EBNF tool that picks the longest match, this is the one habit you
need to unlearn.

## Optional

Reads a rule if it is there, and succeeds either way.

```php
use Phplrt\Parser\Grammar\Optional;

new Optional(ruleId: 1);
```

```pp2
Rule : Sign()? ;
```

If the inner rule does not match, nothing is read and nothing is added to the
result - the parse simply continues from the same place.

## Repetition

Reads a rule as many times as it keeps matching.

```php
use Phplrt\Parser\Grammar\Repetition;

new Repetition(ruleId: 1);                 // zero or more, "*"
new Repetition(ruleId: 1, min: 1);         // one or more,  "+"
new Repetition(ruleId: 1, min: 2, max: 5); // between two and five
```

```pp2
Rule : Number()* ;
Rule : Number()+ ;
Rule : Number(){2,5} ;
```

Repetition is greedy: it reads as many times as it can and does not give any
of them back. It also stops as soon as an iteration reads nothing, so a rule
that matches the empty input cannot loop forever.

## Predicate

Looks at what comes next **without reading it**. Nothing is consumed and
nothing lands in the result - the only thing left is whether it matched.

```php
use Phplrt\Parser\Grammar\Predicate;

new Predicate(ruleId: 1);                    // "&" - must match here
new Predicate(ruleId: 1, isExpected: false); // "!" - must not match here
```

This is how a rule refuses a position that belongs to somebody else. For
example, "a name that is not a function call":

```php
new Concatenation([
    2, // Predicate(ruleId: 1 /* "(" */, isExpected: false)
    3, // Lexeme(T_NAME)
]);
```

EBNF has nothing like this - a predicate describes *how* something is read
rather than *what* the language contains. A grammar file writes it with the
same two signs:

```pp2
Variable : <T_NAME> !::T_PARENTHESIS_OPEN:: ;
Closure  : &::T_FN:: FunctionLiteral() ;
```

See [Predicates](/docs/compiler/grammar).

## Sequences and Single Values

One distinction matters when you write reducers.

`Concatenation` and `Repetition` implement `SequenceInterface`: they recognize
a *list* of things, so their value is a list.

```pp2
Rule : Number() Plus() Number() ;  // $children is an array
Rule : Number()+ ;                 // $children is an array
```

Everything else passes a **single value** through:

```pp2
Rule : Number() | Name() ;   // $children is whatever matched
Rule : Number()? ;           // $children is the Number, or nothing
Rule : <T_DIGIT> ;           // $children is the token
```

That is why a reducer often starts with an `is_array()` check - a rule that
can match one thing *or* several will hand you one thing or several.

More on this in [Results and Reducers](/docs/parser/ast).

## The Interfaces

```
RuleInterface
├── TerminalInterface     - Lexeme
└── ProductionInterface   - Alternation, Optional, Predicate
    └── SequenceInterface - Concatenation, Repetition
```

A *terminal* is matched against the input. A *production* is matched by means
of other rules. A *sequence* is a production whose value is a list.
