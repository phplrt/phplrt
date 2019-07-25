# Abstract Syntax Tree

The name and location offset (in bytes) are part of AST objects 
common capabilities. However, terminals have the ability to retrieve 
values, and non-terminal contain descendants.

As you can see, each node has the `__toString` method, so the **XML** string
of these rules is just a representation of their internal structure.

An abstract syntax tree provides a set of classes 
that can be represented in one of two ways:

## Leaf (terminal)

The leaves is an terminal structures, which are represented inside the grammar as tokens.
 
- `Phplrt\Contracts\Ast\LeafInterface`

## Rule (production)

Ast rules is an non-terminal structures that are part of 
the production of grammar.

- `Phplrt\Contracts\Ast\RuleInterface` 

## Examples

```php
<?php

use Phplrt\Ast\{Leaf, Node};

echo new Node('rule', [
    new Leaf('leaf', 'a'),
    new Leaf('leaf', 'b'),
]);
```

Outputs:

```xml
<rule offset="0">
    <leaf offset="0">a</leaf>
    <leaf offset="0">b</leaf>
</rule>
```
