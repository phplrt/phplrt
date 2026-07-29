# JSON

A JSON parser that produces plain PHP values - the result matches
`json_decode($input, true)` exactly.

```php
$parser->parse(new Source('{"a": 1, "b": [true, null]}'))->value;
// ['a' => 1, 'b' => [true, null]]
```

Every rule returns its value wrapped in a single class:

```php
namespace Json;

final readonly class JsonValue
{
    public function __construct(
        public mixed $value,
    ) {}
}
```

The wrapper is not decoration, it is what makes the grammar work. A reducer
returning a plain **array** has its items spliced into the rule above, so an
object would collapse into a flat list; and a reducer returning **`null`**
means "no result", so `null` would come back as the raw token. An object
means what it says in both cases.

## Grammar

```pp2
%skip  T_WHITESPACE  \s++

%token T_STRING      "(?:[^"\\]|\\.)*+"
%token T_NUMBER      -?\d++(?:\.\d++)?(?:[eE][+-]?\d++)?
%token T_TRUE        true
%token T_FALSE       false
%token T_NULL        null

%token T_BRACE_OPEN     \{
%token T_BRACE_CLOSE    \}
%token T_BRACKET_OPEN   \[
%token T_BRACKET_CLOSE  \]
%token T_COLON          :
%token T_COMMA          ,

%pragma root Value

Value
  : Object()
  | Array()
  | String()
  | Number()
  | True()
  | False()
  | Null()
  ;

// The members arrive as a flat list: key, value, key, value...
Object -> {
    $values = [];
    $children = \is_array($children) ? $children : [];

    for ($i = 0; isset($children[$i + 1]); $i += 2) {
        $values[$children[$i]->value] = $children[$i + 1]->value;
    }

    return new \Json\JsonValue($values);
}
  : ::T_BRACE_OPEN::
      (Member() (::T_COMMA:: Member())*)?
    ::T_BRACE_CLOSE::
  ;

// No reducer: a member is merged into the object as a key/value pair
Member : String() ::T_COLON:: Value() ;

Array -> {
    $values = [];

    foreach (\is_array($children) ? $children : [] as $value) {
        $values[] = $value->value;
    }

    return new \Json\JsonValue($values);
}
  : ::T_BRACKET_OPEN::
      (Value() (::T_COMMA:: Value())*)?
    ::T_BRACKET_CLOSE::
  ;

String -> { return new \Json\JsonValue(\json_decode($children->value)); }
  : <T_STRING>
  ;

Number -> { return new \Json\JsonValue(0 + $children->value); }
  : <T_NUMBER>
  ;

True  -> { return new \Json\JsonValue(true); }  : <T_TRUE> ;
False -> { return new \Json\JsonValue(false); } : <T_FALSE> ;
Null  -> { return new \Json\JsonValue(null); }  : <T_NULL> ;
```

## Usage

```php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Phplrt\Source\Source;

$parser = new Compiler()
    ->load(new File(__DIR__ . '/grammar.pp3'))
    ->getParser();

$data = $parser->parse(new Source($json))->value;
```

Two details worth stealing for your own grammars. `Member` has no reducer, so
its two values are merged into the object's list - which is exactly why the
object reads them in pairs. And `(Value() (::T_COMMA:: Value())*)?` is the
standard shape for a possibly-empty comma-separated list; writing
`(Value() ::T_COMMA::)*` instead would demand a trailing comma.
