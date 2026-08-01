// -----------------------------------------------------------------------------
//  PHPDoc Type Expression Of PHPStan, for hoa/compiler
// -----------------------------------------------------------------------------
//
// The same grammar as "PhpDocType.pp3", written the way hoa/compiler reads it.
//
// Hoa has no reducers, so the tree is built out of its own nodes and the "#"
// markers say where one begins. The result is a tree of objects either way,
// which is what the comparison is about.
//
// @see https://github.com/phpstan/phpdoc-parser/blob/2.3.x/doc/grammars/type.abnf

%skip  T_WHITESPACE             \s+

%token T_CONSTANT_FLOAT         [+\-]?(?:[0-9]+(?:_[0-9]+)*\.(?:[0-9]+(?:_[0-9]+)*)?(?:[eE][+\-]?[0-9]+(?:_[0-9]+)*)?|[0-9]+(?:_[0-9]+)*(?:[eE][+\-]?[0-9]+(?:_[0-9]+)*)|\.[0-9]+(?:_[0-9]+)*(?:[eE][+\-]?[0-9]+(?:_[0-9]+)*)?)
%token T_CONSTANT_INT           [+\-]?(?:0[bB][01]+(?:_[01]+)*|0[oO][0-7]+(?:_[0-7]+)*|0[xX][0-9a-fA-F]+(?:_[0-9a-fA-F]+)*|[0-9]+(?:_[0-9]+)*)
%token T_CONSTANT_STRING        '(?:\\[^\r\n]|[^\r\n\\'])*'|"(?:\\[^\r\n]|[^\r\n\\"])*"

%token T_THIS_VARIABLE          \$this(?![a-zA-Z0-9_\x80-\xff])
%token T_VARIABLE               \$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*

%token T_CONTRAVARIANT          contravariant(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_COVARIANT              covariant(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_SUPER                  super(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_NOT                    not(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_IS                     is(?![a-zA-Z0-9_\-\\\x80-\xff])
%token T_OF                     of(?![a-zA-Z0-9_\-\\\x80-\xff])

%token T_IDENTIFIER             \\?[a-zA-Z_\x80-\xff][a-zA-Z0-9_\-\x80-\xff]*(?:\\[a-zA-Z_\x80-\xff][a-zA-Z0-9_\-\x80-\xff]*)*

%token T_VARIADIC               \.\.\.
%token T_DOUBLE_COLON           ::
%token T_COLON                  :
%token T_EQUAL_SIGN             =
%token T_UNION                  \|
%token T_INTERSECTION           &
%token T_NULLABLE               \?
%token T_WILDCARD               \*
%token T_COMMA                  ,

%token T_PARENTHESES_OPEN       \(
%token T_PARENTHESES_CLOSE      \)
%token T_ANGLE_BRACKET_OPEN     <
%token T_ANGLE_BRACKET_CLOSE    >
%token T_SQUARE_BRACKET_OPEN    \[
%token T_SQUARE_BRACKET_CLOSE   \]
%token T_CURLY_BRACKET_OPEN     \{
%token T_CURLY_BRACKET_CLOSE    \}

#TypeStatement:
    Type()

Type:
    Atomic() ( Union() | Intersection() )?
  | Nullable()

ParenthesizedType:
    <T_VARIABLE> Conditional()
  | Atomic() ( Union() | Intersection() | Conditional() )?
  | Nullable()

#Union:
    ( ::T_UNION:: Atomic() )+

#Intersection:
    ( ::T_INTERSECTION:: Atomic() )+

#Conditional:
    ::T_IS:: <T_NOT>? Atomic()
    ::T_NULLABLE:: Type()
    ::T_COLON:: ParenthesizedType()

#Nullable:
    ::T_NULLABLE:: Atomic()

Atomic:
    <T_CONSTANT_FLOAT>
  | <T_CONSTANT_INT>
  | <T_CONSTANT_STRING>
  | <T_THIS_VARIABLE>
  | ::T_PARENTHESES_OPEN:: ParenthesizedType() ::T_PARENTHESES_CLOSE:: Array()?
  | Identifier() (
      Callable()
    | Generic()
    | ArrayShape()
    | Array()
    | ClassConstant()
    )?

#Identifier:
    <T_IDENTIFIER>
  | <T_IS>
  | <T_NOT>
  | <T_OF>
  | <T_SUPER>
  | <T_COVARIANT>
  | <T_CONTRAVARIANT>

#ClassConstant:
    ::T_DOUBLE_COLON:: Identifier()

#Generic:
    ::T_ANGLE_BRACKET_OPEN::
      GenericTypeArgument() ( ::T_COMMA:: GenericTypeArgument() )*
    ::T_ANGLE_BRACKET_CLOSE::

GenericTypeArgument:
    ( <T_CONTRAVARIANT> | <T_COVARIANT> )? Type()
  | <T_WILDCARD>

#Callable:
    CallableTemplate()?
    ::T_PARENTHESES_OPEN:: CallableParameters()? ::T_PARENTHESES_CLOSE::
    ::T_COLON:: CallableReturnType()

#CallableTemplate:
    ::T_ANGLE_BRACKET_OPEN::
      CallableTemplateArgument() ( ::T_COMMA:: CallableTemplateArgument() )*
    ::T_ANGLE_BRACKET_CLOSE::

#CallableTemplateArgument:
    Identifier()
    ( <T_OF> Type() )?
    ( <T_SUPER> Type() )?
    ( <T_EQUAL_SIGN> Type() )?

CallableParameters:
    CallableParameter() ( ::T_COMMA:: CallableParameter() )*

#CallableParameter:
    Type()
    <T_INTERSECTION>?
    <T_VARIADIC>?
    <T_VARIABLE>?
    <T_EQUAL_SIGN>?

CallableReturnType:
    Identifier() Generic()?
  | Nullable()
  | ::T_PARENTHESES_OPEN:: Type() ::T_PARENTHESES_CLOSE::

#Array:
    ( <T_SQUARE_BRACKET_OPEN> ::T_SQUARE_BRACKET_CLOSE:: )+

#ArrayShape:
    ::T_CURLY_BRACKET_OPEN::
      ArrayShapeItem() ( ::T_COMMA:: ArrayShapeItem() )*
    ::T_CURLY_BRACKET_CLOSE::

#ArrayShapeItem:
    ArrayShapeKey() <T_NULLABLE>? ::T_COLON:: Type()
  | Type()

ArrayShapeKey:
    <T_CONSTANT_STRING>
  | <T_CONSTANT_INT>
  | Identifier()
