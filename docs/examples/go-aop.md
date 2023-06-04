# Go! AOP Pointcuts

Below is an example of a [Go! AOP](https://github.com/goaop/framework) pointcuts grammar

## Grammar

```pp2
/**
 * This file is a set of rules for parsing (syntax analysis) of pointcut syntax.
 *
 * @see https://github.com/goaop/idea-plugin/blob/1.2.1/src/com/aopphp/go/parser/pointcut.bnf
 */

%pragma root Expression

/**
 * This file is a set of rules for lexical analysis of pointcut syntax.
 *
 * @see https://github.com/goaop/idea-plugin/blob/master/src/com/aopphp/go/parser/PointcutLexer.flex
 */

%token  T_THIS              \$this\b

%token  T_ACCESS            (?<=\b)(?i)access\b
%token  T_EXECUTION         (?<=\b)(?i)execution\b
%token  T_WITHIN            (?<=\b)(?i)within\b
%token  T_INIT              (?<=\b)(?i)initialization\b
%token  T_INIT_STATIC       (?<=\b)(?i)staticInitialization\b
%token  T_CFLOW             (?<=\b)(?i)cFlowBelow\b
%token  T_DYNAMIC           (?<=\b)(?i)dynamic\b
%token  T_MATCH             (?<=\b)(?i)matchInherited\b

%token  T_MOD_PUBLIC        (?<=\b)public\b
%token  T_MOD_PRIVATE       (?<=\b)private\b
%token  T_MOD_PROTECTED     (?<=\b)protected\b
%token  T_MOD_FINAL         (?<=\b)final\b


// Note: Keyword "abstract" does not make sense, because
// because pointcuts cannot be applied on abstract methods
//
// %token  T_MOD_ABSTRACT      (?<=\b)abstract\b

// Note: Keyword "static" does not make sense, because
// access method is controlled through tokens "->" and "::"
//
// %token  T_MOD_STATIC        (?<=\b)static\b

%token  T_NS_SEPARATOR      \\
%token  T_ANNOTATION        @
%token  T_LEFT_PAREN        \(
%token  T_RIGHT_PAREN       \)

%token  T_OBJECT_ACCESS     \->
%token  T_STATIC_ACCESS     ::

%token  T_DOUBLE_ASTERISK   \*\*
%token  T_ASTERISK          \*
%token  T_SUBNAMESPACE_SIGN \+

%token  T_ALTERNATION       \|
%token  T_NEGATION          !
%token  T_LOGICAL_OR        \|\|
%token  T_LOGICAL_AND       &&
%token  T_RETURN_HINT       :

%token  T_NAME              \w+

%skip   T_COMMENT           //[^\n]+
%skip   T_WHITESPACE        \s+

NamespacePattern
  : NamespacePatternPart() (
      ::T_NS_SEPARATOR:: NamespacePatternPart()
    )*
  ;

NamePattern
  : NamePatternPart() (
      ::T_ALTERNATION:: NamePatternPart()
    )*
  ;

// -----------------------------------------------------------------------------

NamespacePatternPart
  : NamePatternPart()
  | AnyNamespacedWord()
  ;

NamePatternPart
  : NamePatternItem()+
  ;

NamePatternItem
  : AnyWord()
  | Name()
  ;

AnyNamespacedWord
  : <T_DOUBLE_ASTERISK>
  ;

AnyWord
  : <T_ASTERISK>
  ;

Name
  : <T_ACCESS>
  | <T_EXECUTION>
  | <T_WITHIN>
  | <T_INIT>
  | <T_INIT_STATIC>
  | <T_CFLOW>
  | <T_DYNAMIC>
  | <T_MATCH>
  | <T_MOD_PUBLIC>
  | <T_MOD_PRIVATE>
  | <T_MOD_PROTECTED>
  | <T_MOD_FINAL>
  | <T_NAME>
  ;

MethodModifiers
  : AccessModifierExpression() BehaviourModifierExpression()?
  | BehaviourModifierExpression() AccessModifierExpression()?
  ;

PropertyModifiers
  : AccessModifierExpression()
  ;

//
// -----------------------------------------------------------------------------
//  final
// -----------------------------------------------------------------------------
//

#BehaviourModifierExpression
  : BehaviourModifierNegation()
  | BehaviourModifier()
  ;

#BehaviourModifierNegation
  : ::T_NEGATION:: BehaviourModifier()
  ;

BehaviourModifier
  : <T_MOD_FINAL>
  ;

//
// -----------------------------------------------------------------------------
//  private|public|protected
// -----------------------------------------------------------------------------
//

#AccessModifierExpression
  : AccessModifier() (
      ::T_ALTERNATION:: AccessModifier()
    )*
  ;

AccessModifier
  : <T_MOD_PUBLIC>
  | <T_MOD_PRIVATE>
  | <T_MOD_PROTECTED>
  ;

Pointcut
  : AccessPointcut()
  | AnnotatedAccessPointcut()
  | ExecutionPointcut()
  | AnnotatedExecutionPointcut()
  | WithinPointcut()
  | AnnotatedWithinPointcut()
  | InitializationPointcut()
  | StaticInitializationPointcut()
  | ControlFlowBelowPointcut()
  | DynamicExecutionPointcut()
  | MatchInheritedPointcut()
  | PointcutReference()
  ;

#AccessPointcut
  : ::T_ACCESS:: ::T_LEFT_PAREN::
      PropertyDefinition()
    ::T_RIGHT_PAREN::
  ;

#AnnotatedAccessPointcut
  : ::T_ANNOTATION:: ::T_ACCESS:: ::T_LEFT_PAREN::
      ClassDefinition()
    ::T_RIGHT_PAREN::
  ;

#ExecutionPointcut
  : ::T_EXECUTION:: ::T_LEFT_PAREN:: (
      FunctionDefinition() |
      MethodDefinition()
    ) ::T_RIGHT_PAREN::
  ;

#AnnotatedExecutionPointcut
  : ::T_ANNOTATION:: ::T_EXECUTION:: ::T_LEFT_PAREN::
      ClassDefinition()
    ::T_RIGHT_PAREN::
  ;

#WithinPointcut
  : ::T_WITHIN:: ::T_LEFT_PAREN::
      ClassFilter()
    ::T_RIGHT_PAREN::
  ;

#AnnotatedWithinPointcut
  : ::T_ANNOTATION:: ::T_WITHIN:: ::T_LEFT_PAREN::
      ClassDefinition()
    ::T_RIGHT_PAREN::
  ;

#InitializationPointcut
  : ::T_INIT:: ::T_LEFT_PAREN::
      ClassFilter()
    ::T_RIGHT_PAREN::
  ;

#StaticInitializationPointcut
  : ::T_INIT_STATIC:: ::T_LEFT_PAREN::
      ClassFilter()
    ::T_RIGHT_PAREN::
  ;

#ControlFlowBelowPointcut
  : ::T_CFLOW:: ::T_LEFT_PAREN::
      ExecutionPointcut()
    ::T_RIGHT_PAREN::
  ;

#DynamicExecutionPointcut
  : ::T_DYNAMIC:: ::T_LEFT_PAREN::
      MethodDefinition()
    ::T_RIGHT_PAREN::
  ;

#MatchInheritedPointcut
  : ::T_MATCH:: ::T_LEFT_PAREN:: ::T_RIGHT_PAREN::
  ;

#PointcutReference
  : PointcutReferenceContext() ::T_OBJECT_ACCESS:: PropertyDefinitionBody()
  ;

#PointcutReferenceContext
  : NamespacePattern()
  | <T_THIS>
  ;

//
// -----------------------------------------------------------------------------
//  Access Modifiers
// -----------------------------------------------------------------------------
//

#AccessType
  : ObjectAccess()
  | StaticAccess()
  ;

#ObjectAccess
  : <T_OBJECT_ACCESS>
  ;

#StaticAccess
  : <T_STATIC_ACCESS>
  ;

//
// -----------------------------------------------------------------------------
//  Function Reference
// -----------------------------------------------------------------------------
//

#FunctionDefinition
  : NamePattern() FunctionDefinitionArguments()
  ;

#FunctionDefinitionArguments
  : FunctionAnyArguments()
  | FunctionNoArguments()
  ;

#FunctionNoArguments
  : ::T_LEFT_PAREN:: ::T_RIGHT_PAREN::
  ;

#FunctionAnyArguments
  : ::T_LEFT_PAREN:: ::T_ASTERISK:: ::T_RIGHT_PAREN::
  ;

//
// -----------------------------------------------------------------------------
//  Property Reference
// -----------------------------------------------------------------------------
//

PropertyDefinition
  : PropertyModifiers()?
    ClassFilter() AccessType() PropertyDefinitionBody()
  ;

PropertyDefinitionBody
  : NamePattern()
  ;

//
// -----------------------------------------------------------------------------
//  Method Reference
// -----------------------------------------------------------------------------
//

MethodDefinition
  : MethodModifiers()?
    ClassFilter() AccessType() FunctionDefinition()
  ;

//
// -----------------------------------------------------------------------------
//  Class Reference
// -----------------------------------------------------------------------------
//

#ClassDefinition
  : NamespacePattern()
  ;

ClassInstanceOfDefinition
  : NamespacePattern() ::T_SUBNAMESPACE_SIGN::
  ;

ClassFilter
  : ClassInstanceOfDefinition()
  | ClassDefinition()
  ;

#Expression
  : AlternatedExpression()?
  ;

#AlternatedExpression
  : ConjugatedExpression() (::T_LOGICAL_OR:: ConjugatedExpression())+
  | ConjugatedExpression()
  ;

#ConjugatedExpression
  : NegatedExpression() (::T_LOGICAL_AND:: NegatedExpression())+
  | NegatedExpression()
  ;

#NegatedExpression
  : ::T_NEGATION:: GroupExpression()
  | GroupExpression()
  ;

#GroupExpression
  : ::T_LEFT_PAREN:: Expression() ::T_RIGHT_PAREN::
  | SinglePointcut()
  ;

SinglePointcut -> {
    return new Ast\Expression\PointcutExpressionNode();
}
  : Pointcut()
  ;
```

## Execution

```php
<?php
use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;

$compiler = new Compiler();
$compiler->load(File::fromPathname('path/to/grammar-file.pp2'));

$result = $compiler->parse('execution(final public Example\Aspect\*->method*(*))');

echo $result;
```

## Result

```xml
<Expression offset="0">
    <AlternatedExpression offset="0">
        <ConjugatedExpression offset="0">
            <NegatedExpression offset="0">
                <GroupExpression offset="0">
                    <ExecutionPointcut offset="0">
                        <BehaviourModifierExpression offset="10">
                            <T_MOD_FINAL offset="10">final</T_MOD_FINAL>
                        </BehaviourModifierExpression>
                        <AccessModifierExpression offset="16">
                            <T_MOD_PUBLIC offset="16">public</T_MOD_PUBLIC>
                        </AccessModifierExpression>
                        <ClassDefinition offset="23">
                            <T_NAME offset="23">Example</T_NAME>
                            <T_NAME offset="31">Aspect</T_NAME>
                            <T_ASTERISK offset="38">*</T_ASTERISK>
                        </ClassDefinition>
                        <AccessType offset="39">
                            <ObjectAccess offset="39">
                                <T_OBJECT_ACCESS offset="39">-></T_OBJECT_ACCESS>
                            </ObjectAccess>
                        </AccessType>
                        <FunctionDefinition offset="41">
                            <T_NAME offset="41">method</T_NAME>
                            <T_ASTERISK offset="47">*</T_ASTERISK>
                            <FunctionDefinitionArguments offset="48">
                                <FunctionAnyArguments offset="48">
                                </FunctionAnyArguments>
                            </FunctionDefinitionArguments>
                        </FunctionDefinition>
                    </ExecutionPointcut>
                </GroupExpression>
            </NegatedExpression>
        </ConjugatedExpression>
    </AlternatedExpression>
</Expression>
```
