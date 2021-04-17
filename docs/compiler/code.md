# Code Delegation

You can tell the compiler which php class to include the desired grammar rule using 
keyword `->` after name of rule definition. In this case, each processed rule will 
create an instance of target class (or code).

## Delegate

For example every "`Digit`" rule must be represented as an 
instance of `ExampleAstNode` class, and all children (the result of execution)
will be passed to the constructor arguments.

```ebnf
#Digit -> ExampleAstNode
  : <T_DIGIT> 
  ;
```

In this case, the rule class can look like this:

```php
<?php

use Phplrt\Contracts\Ast\NodeInterface;use Phplrt\Contracts\Lexer\TokenInterface;

class ExampleAstNode implements NodeInterface
{
    private int $digit;

    public function __construct($state, TokenInterface $digit, int $offset) 
    {
        $this->digit = (int)$digit->getValue();
    }

    /**
     * The required method of NodeInterface, which should return 
     * children AST nodes.
     * 
     * In this case, the node is empty, so the iterator returns nothing.
     * 
     * @return \Traversable|NodeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \EmptyIterator();
    }
}
```

## PHP Code

Alternatively, you can use a real PHP code inside block `{ ... }` 
constructions. The result (meaning of expression `return XXX`) can be 
an any PHP value except `null`.

```ebnf
#Digit -> {
    var_dump($children);

    return new ExampleAstNode($children->getName());
}
  : <T_DIGIT> 
  ;
```

Note the use of the `$children` variable. The following variables are 
available inside each block:

- `$ctx` - Contains an object (an instance of `Phplrt\Parser\ContextInterface`) 
    of the current context of program execution.
    
- `$children` - Contains the result of executing child rules (except `null` value).

- `$file` - The source/file object (instance of `Phplrt\Contracts\Source\ReadableInterface`) 
    that is currently being processed by the parser.
    
- `$source` - Same with `$file`.

- `$offset` - The current offset (in bytes) relative to the beginning of the 
    file that the parser is currently processing.
    
- `$token` - The current token (instance of `Phplrt\Contracts\Lexer\TokenInterface`) 
    that is currently being processed by the parser.
    
- `$state` - The current parser's state.

- `$rule` - The rule (instance of `Phplrt\Contracts\Grammar\RuleInterface`) 
    that is currently being processed by the parser.
