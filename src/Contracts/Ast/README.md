<p align="center">
    <a href="https://railt.org"><img src="https://avatars2.githubusercontent.com/u/49816277?s=128" width="128" alt="Phplrt" /></a>
</p>

## Abstract Syntax Tree Contracts

A set of interfaces for abstraction over abstract syntax tree.

The `Phplrt\Contracts\Ast\NodeInterface` is the main interface of AST elements.
It contains two methods that determine its type (`getType(): int`) and position 
(`getOffset(): int`) in bytes relative to the start of the source text.

In addition, it contains two implementations of the interfaces. 
The `Phplrt\Contracts\Ast\ProvidesAttributesInterface` defines a set of 
additional metadata of the node (attributes), and the 
`Phplrt\Contracts\Ast\ProvidesChildrenInterface` defines node's descendants.

### Attributes

Attributes are arbitrary user data of an AST node. The values may contain 
information about the file in which the node is defined, may be its type or 
any other specific information.

A `NodeInterface` implements the read only immutable interface that does not 
contain attribute mutation methods.

In the case that your implementation allows to change the attributes of a 
node, then you need to additionally implement the 
`Phplrt\Contracts\Ast\MutatesAttributesInterface`, which defines methods for 
deleting, adding, and changing attributes.

```php
class ExampleNode implements NodeInterface, MutatesAttributesInterface
{
    // ...
}
```

### Child Nodes

The method which should return a set of child nodes is defined by the 
`Phplrt\Contracts\Ast\ProvidesChildrenInterface` interface.

It is a compatible with `\Traversable` and can be used as an iterator:

```php
foreach ($ast as $child) { ... }
```
