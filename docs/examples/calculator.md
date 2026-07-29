# Calculator

An expression evaluator with operator precedence, brackets and unary minus.
The result is a number, not a tree - the reducers do the arithmetic as the
rules are reduced.

```php
2 + 2 * 2       // 6
(2 + 2) * 2     // 8
-(2 + 3)        // -5
```

Precedence is not configured anywhere: it comes out of the shape of the
grammar. The loosest operator sits at the top and each rule is written in
terms of the one below it, so `2 + 2 * 2` can only group one way.

## Grammar

```pp2
%skip  T_WHITESPACE  \s++

%token T_NUMBER      \d++(?:\.\d++)?
%token T_PLUS        \+
%token T_MINUS       \-
%token T_MUL         \*
%token T_DIV         /

%token T_PARENTHESIS_OPEN   \(
%token T_PARENTHESIS_CLOSE  \)

%pragma root Expression

// a + b - c
Expression -> {
    if (!\is_array($children)) {
        return $children;
    }

    $result = \array_shift($children);

    while ($children !== []) {
        $operator = \array_shift($children);
        $right = \array_shift($children);

        $result = $operator->value === '+' ? $result + $right : $result - $right;
    }

    return $result;
}
  : Term() ((<T_PLUS> | <T_MINUS>) Term())*
  ;

// a * b / c
Term -> {
    if (!\is_array($children)) {
        return $children;
    }

    $result = \array_shift($children);

    while ($children !== []) {
        $operator = \array_shift($children);
        $right = \array_shift($children);

        $result = $operator->value === '*' ? $result * $right : $result / $right;
    }

    return $result;
}
  : Unary() ((<T_MUL> | <T_DIV>) Unary())*
  ;

// -a
Unary -> {
    if (!\is_array($children)) {
        return $children;
    }

    return -$children[1];
}
  : <T_MINUS> Unary()
  | Primary()
  ;

// The brackets are read but dropped, so a group is a list of one value
Primary -> { return \is_array($children) ? $children[0] : $children; }
  : ::T_PARENTHESIS_OPEN:: Expression() ::T_PARENTHESIS_CLOSE::
  | Number()
  ;

Number -> { return (float) $children->value; }
  : <T_NUMBER>
  ;
```

## Usage

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

echo $parser->parse(new Source('(2 + 2) * 2')); // 8
```

Every reducer starts with an `is_array()` check because a rule that can match
one thing *or* a sequence hands you one thing or an array: `2` reaches
`Expression` as a single value, `2 + 2` as `[2.0, Token('+'), 2.0]`.
