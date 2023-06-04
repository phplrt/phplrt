# Grammar

Each language consists of words that are added to sentences. And for the correct
construction of the proposal, some rules are needed. Such rules are called **grammar**.

Let's try to create the corresponding grammar for the calculator, which can add 
two numbers. If you are familiar with alternative grammars (Antlr, BNF, EBNF, Hoa, etc.), 
then it will not be difficult for you.

```pp2
(* "sum" is a rule that determines the sequence of a number, an addition symbol and one more number *)
sum = digit plus digit ;

(* "digit" is one of the available numeric characters *)
digit = "0" | "1" | "2" | "3" | "4" | "5" | "6" | "7" | "8" | "9" ;

(* "plus" is a plus sign. Incredibly! *)
plus = "+" ;
```

The grammar of Phplrt is partly different from the original EBNF. 
In this way, let's restructure the same rule into the grammar of the Phplrt.

```pp2
// The rule "digit" can be replaced by a simple lexeme, 
// which can be expressed in a PCRE "\d".
%token  T_DIGIT         \d

// The same applies to the "+" token.
%token  T_PLUS          \+

// All whitespace chars must be ignored.
%skip   T_WHITESPACE    \s+

// Now we need to determine the "sum" rule, which will correspond 
// to the previous version.
#Sum: <T_DIGIT> ::T_PLUS:: <T_DIGIT> ;
```

In order to test the performance simply use the reading and 
playing grammar on the fly!

```php
use Phplrt\Compiler\Compiler;

$compiler = new Compiler();
$compiler->load('
    /**
     * Grammar sources
     */
    %token T_DIGIT      \d
    %token T_PLUS       \+
    %skip  T_WHITESPACE \s+
    
    #Sum
      : <T_DIGIT> ::T_PLUS:: <T_DIGIT> 
      ;
');

echo $compiler->parse('2 + 2');
```

On the output you will take an [AST](https://en.wikipedia.org/wiki/Abstract_syntax_tree), which will 
be serialized in XML by the `echo` operator and which will look like this:

```xml
<Sum offset="0">
    <T_DIGIT offset="0">2</T_DIGIT>
    <T_DIGIT offset="2">2</T_DIGIT>
</Sum>
```

> The naming register does not matter, but it is recommended that you name the tokens in upper case ("TOKEN_NAME"), 
and the rules with a capital letter ("RuleName"). Such recommendations will help you in the future easier to 
navigate in the existing grammar.

## Definitions

In the Phplrt grammar there are 5 types of definitions:

- `%token name regex` - Definition of a name and value of a token.
- `%skip name regex` - Definition of a name and value of a skipped token. Such tokens will be ignored and allowed anywhere in the grammar.
- `%pragma name value` - Rules for the configuration of a lexer and a parser.
- `%include path/to/file` - Link to another grammar file.
- `rule` or `#rule` - The grammar rule.

## Comments

In the Phplrt grammar, there are two types of C-like commentaries:

- `// Inline comment` - This comment type begins with two slashes and ends with an end of the line.
- `/* Multiline comment */` - This comment type begins with `/*` symbols and ends with a `*/` symbol.

## Output Control

You probably already noticed that in grammar, the definitions 
of tokens look a little different: `<TOKEN>` and `::TOKEN::`.

This way of determining the tokens inside the grammar tells the compiler
whether to print the ordered token as a result or not. It is for this reason that the token 
"plus" was ignored, because We do not need information about this token, 
but the values of "digit" tokens are important to us.

- `<TOKEN>` - Keep token in AST.
- `::TOKEN::` - Hide token from AST.

## Declaring rules

Each rule starts with the name of this rule. In addition, each rule can be marked with a `#` symbol that indicates 
that the rule **should be kept** in the AST.

- `#Rule` - The rule saves its name as a state.
- `Rule` - The rule is optimized, and the state is determined by the identifier.

After the name there is a production (body) of this rule, which are separated by 
one of the valid characters: `=` or `:`. The separator character **does not matter** and is 
present as compatibility with other grammars. In addition, the rule can end with an _optional_ `;` char.

The constructions of the PP2 language are the following:

- `rule()` to call a rule,
- `<token>` and `::token::` to declare a lexeme.
- `|` for a disjunction (an "alternation").
- `(…)` for a group.
- `e?` to say that `e` is **optional** (0 or 1 times).
- `e+` to say that `e` can be present **1 or more** times.
- `e*` to say that `e` can be present **0 or more** times.
- `e{x,y}` (`e{,y}`, `e{x,}` or `e{x}`) to say that `e` can be present **between x and y** times.
- `#rule` to create a rule node in the resulting tree.

Finally, the grammar of the PP2 language 
is [written with the PP2 language](https://github.com/phplrt/phplrt/blob/master/src/Compiler/Resources/pp2/grammar.pp2). 

Let's try to add support for the remaining symbols of the 
calculator: Moderation, Division and Subtraction; and at the same time slightly 
improve the rules of the lexer.

```pp2
%skip  T_WHITESPACE     \s+

%token T_DIGIT          \-?\d+
%token T_PLUS           \+
%token T_MINUS          \-
%token T_DIV            /
%token T_MUL            \*

#Expression
  : Operation() 
  ;
    
Operation
  : <T_DIGIT> (
      Addition() | 
      Division() | 
      Subtraction() | 
      Multiplication()
    )? 
  ;

#Addition
  : ::T_PLUS:: Operation() 
  ;

#Division
  : ::T_DIV:: Operation() 
  ;

#Subtraction
  : ::T_MINUS:: Operation() 
  ;

#Multiplication
  : ::T_MUL:: Operation() 
  ;
```

Simple expression `4 + 8 - 15 * 16 / 23 + -42` will be parsed into the followed tree:

```xml
<Ast>
  <Expression offset="0">
    <T_DIGIT offset="0">4</T_DIGIT>
    <Addition offset="2">
      <T_DIGIT offset="4">8</T_DIGIT>
      <Subtraction offset="6">
        <T_DIGIT offset="8">15</T_DIGIT>
        <Multiplication offset="11">
          <T_DIGIT offset="13">16</T_DIGIT>
          <Division offset="16">
            <T_DIGIT offset="18">23</T_DIGIT>
            <Addition offset="21">
              <T_DIGIT offset="23">-42</T_DIGIT>
            </Addition>
          </Division>
        </Multiplication>
      </Subtraction>
    </Addition>
  </Expression>
</Ast>
```

Note that the grammar is quite trivial and does not contain the priorities of the operators.
