# Delegates

Each **Rule** can be represented as its own structure, different from the 
standard. To do this, you only need to define in the 
parser when to delegate this authority.

To begin with, we should specify in the grammar which rule 
or token should delegate its authority to the external class:

```php
//
// BNF 
// operation = T_PLUS | T_MINUS ;
//
$delegates = ['operation' => Operation::class];

$grammar = new Grammar([...], 'operation', $delegates);
```

The definition of such a class might look like this. 
Please note that it must be an implementation `RuleInterface` or `LeafInterface`.

```php
class Operation extends Rule 
{
    public function isMinus(): bool 
    {
        return $this->getChild(0)->getName() === 'T_MINUS';
    }
    
    public function isPlus(): bool 
    {
        return $this->getChild(0)->getName() === 'T_PLUS';
    }
}
```

And now, as an **operation** rule, we get the instance of `Operation` class:

```php
$ast = (new Parser($lexer, $grammar))->parse(File::fromSources('2 + 2'));

$operation = $ast->getChild(0); // Operation::class

$operation->isPlus();   // true
$operation->isMinus();  // false
```
