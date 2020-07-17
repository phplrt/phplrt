# JSON5 Example

Below is an example of a simple json5 grammar.

## Grammar

```ebnf
/**
 * --------------------------------------------------------------------------
 *  JSON5 Punctuators and Keywords
 * --------------------------------------------------------------------------
 *
 * The lexical grammar for JSON5 has as its terminal symbols characters
 * (Unicode code units) that conform to the rules for JSON5SourceCharacter.
 * It defines a set of productions, starting from the goal symbol
 * JSON5InputElement, that describe how sequences of such characters are
 * translated into a sequence of input elements.
 *
 * Input elements other than white space and comments form the terminal
 * symbols for the syntactic grammar for JSON5 and are called tokens.
 * These tokens are the identifiers, literals, and punctuators of the
 * JSON5 language. Simple white space and comments are discarded and do
 * not appear in the stream of input elements for the syntactic grammar.
 *
 * Productions of the lexical grammar are distinguished by having two
 * colons "::" as separating punctuation.
 *
 * @see https://spec.json5.org/#lexical-grammar
 *
 * --------------------------------------------------------------------------
 *  Comments
 * --------------------------------------------------------------------------
 *
 * Comments can be either single or multi-line. Multi-line comments cannot
 * nest. Comments may appear before and after any JSON5Token.
 *
 * A single line comment begins with two soliduses and ends with a
 * LineTerminator or the end of the document. All Unicode characters may
 * be placed within the start and end, except for a LineTerminator.
 *
 * A multi-line comment begins with a solidus and an asterisk and ends
 * with an asterisk and a solidus. All Unicode characters may be placed
 * within the start and end, except for an asterisk followed by a solidus.
 *
 * @see https://spec.json5.org/#comments
 *
 */

%skip T_COMMENT                 //[^\n]*\n
%skip T_DOC_COMMENT             /\*.*?\*/


%token T_BRACKET_OPEN           \[
%token T_BRACKET_CLOSE          \]
%token T_BRACE_OPEN             {
%token T_BRACE_CLOSE            }
%token T_COLON                  :
%token T_COMMA                  ,
%token T_PLUS                   \+
%token T_MINUS                  \-

/**
 * --------------------------------------------------------------------------
 *  Values
 * --------------------------------------------------------------------------
 *
 * A JSON5 value must be an object, array, string, or number, or one of the
 * three literal names "true", "false", or "null".
 *
 * @see https://spec.json5.org/#values
 *
 */

%token T_BOOL_TRUE              (?<=\b)true\b
%token T_BOOL_FALSE             (?<=\b)false\b
%token T_NULL                   (?<=\b)null\b


/**
 * --------------------------------------------------------------------------
 *  Numbers
 * --------------------------------------------------------------------------
 *
 * The representation of numbers is similar to that used in most programming
 * languages. A number may be represented in in base 10 using decimal
 * digits, base 16 using hexadecimal digits, or the IEEE 754 values positive
 * infinity, negative infinity, or NaN.
 *
 * @see https://spec.json5.org/#values
 *
 */

%token T_INF                    (?<=\b)Infinity\b
%token T_NAN                    (?<=\b)NaN\b
%token T_HEX_NUMBER             0x([0-9a-fA-F]+)

// Float number with leading (LD) floating point
%token T_FLOAT_LD_NUMBER        [0-9]*\.[0-9]+

// Float number with trailing (TG) floating point
%token T_FLOAT_TG_NUMBER        [0-9]+\.[0-9]*

%token T_INT_NUMBER             [0-9]+

%token T_EXPONENT_PART          [eE]((?:\-|\+)?[0-9]+)
%token T_IDENTIFIER             [\$_A-Za-z][\$_0-9A-Za-z]*


/**
 * --------------------------------------------------------------------------
 *  Strings
 * --------------------------------------------------------------------------
 *
 * A string begins and ends with single or double quotation marks. The same
 * quotation mark that begins a string must also end the string. All
 * Unicode characters may be placed within the quotation marks, except
 * for the characters that must be escaped: the quotation mark used to
 * begin and end the string, reverse solidus, and line terminators.
 *
 * @see https://spec.json5.org/#strings
 *
 */

%token T_DOUBLE_QUOTED_STRING   "([^"\\]*(?:\\.[^"\\]*)*)"
%token T_SINGLE_QUOTED_STRING   '([^'\\]*(?:\\.[^'\\]*)*)'


/**
 * White space may appear before and after any JSON5Token.
 *
 * @see https://spec.json5.org/#white-space
 */

%skip T_HORIZONTAL_TAB         \x09
%skip T_LINE_FEED              \x0A
%skip T_VERTICAL_TAB           \x0B
%skip T_FORM_FEED              \x0C
%skip T_CARRIAGE_RETURN        \x0D
%skip T_WHITESPACE             \x20
%skip T_NON_BREAKING_SPACE     \xA0
%skip T_LINE_SEPARATOR         \x2028
%skip T_PARAGRAPH_SEPARATOR    \x2029
%skip T_UTF32BE_BOM            ^\x00\x00\xFE\xFF
%skip T_UTF32LE_BOM            ^\xFE\xFF\x00\x00
%skip T_UTF16BE_BOM            ^\xFE\xFF
%skip T_UTF16LE_BOM            ^\xFF\xFE
%skip T_UTF8_BOM               ^\xEF\xBB\xBF
%skip T_UTF7_BOM               ^\x2B\x2F\x76\x38\x2B\x2F\x76\x39\x2B\x2F\x76\x2B\x2B\x2F\x76\x2F

/**
 * --------------------------------------------------------------------------
 *  JSON5 Grammar
 * --------------------------------------------------------------------------
 *
 * The JSON5 Data Interchange Format (JSON5) is a superset of JSON that
 * aims to alleviate some of the limitations of JSON by expanding its
 * syntax to include some productions from ECMAScript 5.1.
 *
 * Similar to JSON, JSON5 can represent four primitive types
 * (strings, numbers, Booleans, and null) and two structured types
 * (objects and arrays).
 *
 * A string is a sequence of zero or more Unicode characters. Note that
 * this citation references the latest version of Unicode rather than a
 * specific release. It is not expected that future changes in the Unicode
 * specification will impact the syntax of JSON5.
 *
 * An object is an unordered collection of zero or more name/value pairs,
 * where a name is a string or identifier and a value is a string, number,
 * Boolean, null, object, or array.
 *
 * An array is an ordered sequence of zero or more values.
 *
 * @see https://spec.json5.org/
 * @see http://www.unicode.org/versions/Unicode11.0.0/
 * @see https://tools.ietf.org/html/rfc7159
 * @see https://www.ecma-international.org/ecma-262/5.1/
 *
 */

#Json
  : Value()
  ;

#Value
  : Object()
  | Array()
  | Boolean()
  | Null()
  | NaN()
  | Inf()
  | String()
  | Hex()
  | Exponential()
  | Float()
  | Int()
  ;

// { key: val, key: val }
// ^^^^^^^^^^^^^^^^^^^^^^
#Object -> {
    return new \Ast\ObjectNode($token->getOffset(), $children);
}
  : ::T_BRACE_OPEN::
      ( ObjectMember() (::T_COMMA:: ObjectMember())* ::T_COMMA::? )?
    ::T_BRACE_CLOSE::
  ;

// { key: val, key: val }
//   ^^^^^^^^
#ObjectMember -> {
    return new \Ast\ObjectMemberNode($token->getOffset(), ...$children);
}
  : (String() | Identifier()) ::T_COLON:: Value()
  ;

// [ a, b, c ]
// ^^^^^^^^^^^
#Array -> {
    return new \Ast\ArrayNode($token->getOffset(), $children);
}
  : ::T_BRACKET_OPEN::
      ( Value() (::T_COMMA:: Value())* ::T_COMMA::? )?
    ::T_BRACKET_CLOSE::
  ;

// "string"
// ^^^^^^^
#String -> {
    return new \Ast\StringNode($token->getOffset(), \substr($children->getValue(), 1, -1));
}
  : <T_DOUBLE_QUOTED_STRING>
  | <T_SINGLE_QUOTED_STRING>
  ;

// true false
// ^^^^ ^^^^^
#Boolean -> {
    return new \Ast\BooleanNode($token->getOffset(),
        $children->getName() === 'T_BOOL_TRUE'
    );
}
  : <T_BOOL_TRUE>
  | <T_BOOL_FALSE>
  ;

// null
// ^^^^
#Null -> {
    return new \Ast\NullNode($token->getOffset());
}
  : <T_NULL>
  ;

// identifier
// ^^^^^^^^^^
#Identifier -> {
    return new \Ast\IdentifierNode($token->getOffset(), $children->getValue());
}
  : <T_IDENTIFIER>
  ;

// +42 -23
// ^   ^
#Signed -> {
    return \is_array($children) || $children->getName() === 'T_PLUS';
}
  : (<T_PLUS> | <T_MINUS>)?
  ;

// +42e3 -23e-4
//    ^^    ^^^
#Inf -> {
    return new \Ast\InfinityNumberNode($token->getOffset(), \reset($children));
}
  : Signed() ::T_INF::
  ;

// NaN
// ^^^
#NaN -> {
    return new \Ast\NotANumberNode($token->getOffset());
}
  : <T_NAN>
  ;

// 0.4, .42, 42.
// ^^^  ^^^  ^^^
#Float -> {
    return new \Ast\FloatNumberNode($token->getOffset(), \reset($children), \end($children)->getValue());
}
  : Signed() (<T_FLOAT_LD_NUMBER> | <T_FLOAT_TG_NUMBER>)
  ;

// 42
// ^^
#Int -> {
    return new \Ast\IntNumberNode($token->getOffset(), \reset($children), \end($children)->getValue());
}
  : Signed() <T_INT_NUMBER>
  ;

// 42e3  .4e3
//   ^^    ^^
#Exponential -> {
    return new \Ast\ExponentialNumberNode($token->getOffset(), \reset($children), \end($children)->getValue());
}
  : (Float() | Int()) <T_EXPONENT_PART>
  ;

// 0xDEADBEEF
// ^^^^^^^^^^
#Hex -> {
    return new \Ast\HexadecimalNumberNode($token->getOffset(), \reset($children), \end($children)->getValue());
}
  : Signed() <T_HEX_NUMBER>
  ;
```

## Execution

```php
<?php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

$compiler = new Compiler();
$compiler->load(File::fromPathname('path/to/grammar-file.pp2'));

echo $compiler->parse('{ key: "value" }');
```

## Result

```xml
<Json offset="0">
    <Value offset="0">
        <Object offset="0">
            <ObjectMember offset="2">
                <Identifier offset="2">
                    <T_IDENTIFIER offset="2">key</T_IDENTIFIER>
                </Identifier>
                <Value offset="7">
                    <String offset="7">
                        <T_DOUBLE_QUOTED_STRING offset="7">"value"</T_DOUBLE_QUOTED_STRING>
                    </String>
                </Value>
            </ObjectMember>
        </Object>
    </Value>
</Json>
```
