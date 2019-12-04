# Abstract Syntax Tree

The name and location offset (in bytes) are part of AST objects 
common capabilities. However, terminals have the ability to retrieve 
values, and non-terminal contain descendants.

As you can see, each node has the `__toString` method, so the **XML** string
of these rules is just a representation of their internal structure.

An abstract syntax tree provides a set of classes 
that can be represented in one of two ways:

## AST Builder

You can independently determine the rules for constructing AST using 
custom builder.

```php
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class MyBuilder implements BuilderInterface
{
    public function build(ReadableInterface $source, RuleInterface $rule, TokenInterface $token, $state, $children)
    {
        switch ($state) {
            case 0: return new MyExampleNode($children);
            case 1: return new MyAnotherExampleNode($children);
        }   

        return null;
    }
}
```

### Usage

```php
use Phplrt\Parser\Parser;$parser = new Parser($lexer, $grammar, [
    Parser::CONFIG_AST_BUILDER => new MyBuilder()
]);
```
