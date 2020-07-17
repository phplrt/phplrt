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
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Parser\ContextInterface;

class MyBuilder implements BuilderInterface
{
    public function build(ContextInterface $ctx, $children)
    {
        switch ($ctx->getState()) {
            case 0: return new MyExampleNode($children);
            case 1: return new MyAnotherExampleNode($children);
        }   

        return null;
    }
}
```

### Usage

```php
use Phplrt\Parser\Parser;

$parser = new Parser($lexer, $grammar, [
    Parser::CONFIG_AST_BUILDER => new MyBuilder()
]);
```
