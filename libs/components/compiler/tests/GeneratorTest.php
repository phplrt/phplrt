<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\DuplicateConstantException;
use Phplrt\Compiler\Exception\InvalidClassNameException;
use Phplrt\Compiler\Exception\UnsupportedClassModifierException;
use Phplrt\Compiler\Exception\UnsupportedEmbeddedLexerException;
use Phplrt\Compiler\Exception\UnsupportedReducerException;
use Phplrt\Compiler\Generator\ClassModifier;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Compiler\Generator\PhpCodePrinter;
use Phplrt\Compiler\Generator\TargetPhpVersion;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Filter\Group;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class GeneratorTest extends TestCase
{
    private array $files = [];

    #[AfterTest]
    protected function tearDown(): void
    {
        foreach ($this->files as $pathname) {
            @\unlink($pathname);
        }

        $this->files = [];
    }

    public function testGeneratedParserRecognizesTheGrammar(): void
    {
        $parser = $this->generate('grammar.pp2');

        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 39')), 42);
    }

    public function testKeptRuleIsDeclaredAsAConstant(): void
    {
        $output = (string) $this->generateOf(<<<'PP3'
            %token T_NUMBER \d++

            Sum : Number()+ ;

            #Number -> { return (int) $children->value; }
              : <T_NUMBER>
              ;
            PP3)->withClassName('KeptRuleParser');

        $assertion = Assert::string($output)
            ->contains('public function withInitial(int $rule)');

        if (\PHP_VERSION_ID >= 80300) {
            $assertion->contains('public const int Number = ');
        } else {
            $assertion->contains('public const Number = ');
        }

    }

    public function testGrammarWithoutKeptRulesDeclaresNoEntrypoints(): void
    {
        $output = (string) $this->generateOf("%token T_A a\nA : <T_A> ;")
            ->withClassName('PlainParser');

        Assert::string($output)->notContains('public function withInitial(');
    }

    public function testTokenAndRuleNamedTheSameWayAreReported(): void
    {
        Expect::exception(DuplicateConstantException::class)
        ->withMessageContaining('"Number" constant');

        (string) $this->generateOf(<<<'PP3'
            %token Number \d++

            Sum : Number()+ ;

            #Number : <Number> ;
            PP3)->withClassName('ClashingParser');
    }

    public function testGeneratedLexerReadsTheFragments(): void
    {
        $parser = $this->generate('states.pp2');

        $result = $parser->parse(StringSource::createFromString('"hello",'));

        Assert::array($result);
        Assert::instanceOf($result[0], TokenEmbedding::class);
        Assert::same(\array_column($result[0]->children, 'name'), ['T_TEXT', 'T_QUOTE_CLOSE']);
    }

    public function testNamespaceIsGenerated(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withNamespaceName('Example\\Some');

        Assert::string($code)->contains("\nnamespace Example\\Some;\n");
    }

    public function testClassImportsAreGenerated(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassImport('App\\Node')
            ->withClassImport('App\\Other\\Node', as: 'OtherNode');

        Assert::string($code)->contains("\nuse App\\Node;\nuse App\\Other\\Node as OtherNode;\n");
    }

    #[DataSet([TargetPhpVersion::Php81, false], 'PHP 8.1 (no typed constants)')]
    #[DataSet([TargetPhpVersion::Php82, false], 'PHP 8.2 (no typed constants)')]
    #[DataSet([TargetPhpVersion::Php83, true], 'PHP 8.3 (supports typed constants)')]
    #[DataSet([TargetPhpVersion::Php84, true], 'PHP 8.4 (supports typed constants)')]
    #[DataSet([TargetPhpVersion::Php85, true], 'PHP 8.5 (supports typed constants)')]
    #[DataSet([TargetPhpVersion::Php86, true], 'PHP 8.6 (supports typed constants)')]
    public function testNamedTokenIsReferredByConstant(TargetPhpVersion $php, bool $hasTypedConstant): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
                ->withTargetPhpVersion($php);

        $string = Assert::string($code)
            ->contains('new \\Phplrt\\Parser\\Grammar\\Lexeme(self::T_NUMBER, true)');

        if ($hasTypedConstant) {
            $string->contains('public const int T_NUMBER = 0;');
        } else {
            $string->contains('public const T_NUMBER = 0;');
        }
    }

    public function testInlineTokenIsReferredByIdentifier(): void
    {
        $code = (string) $this->compile('states.pp2')->generate();

        Assert::string($code)->contains('new \\Phplrt\\Parser\\Grammar\\Lexeme(2, false)');
    }

    public function testFragmentIsWrittenDownOnce(): void
    {
        $code = (string) $this->compile('states.pp2')->generate();

        Assert::string($code)
            ->contains('$state_string = new \\Phplrt\\Lexer\\Lexer(')
            ->contains('0 => $state_string,');
    }

    public function testReducerIsGeneratedAsAMethod(): void
    {
        $code = (string) $this->compile('grammar.pp2')->generate();

        Assert::string($code)
            ->contains('private static function reduceExpression(\\Phplrt\\Parser\\Context $ctx, mixed $children): mixed')
            ->contains('0 => self::reduceExpression(...),');
    }

    public function testReducerOfTheParserIsNotStatic(): void
    {
        $code = (string) $this->generateOf(<<<'PP2'
            %token T_NAME [a-z]++

            Name -> { return $this->rename($children); } : <T_NAME> ;
            PP2);

        Assert::string($code)
            ->contains('private function reduceName(\\Phplrt\\Parser\\Context $ctx, mixed $children): mixed')
            ->contains('0 => $this->reduceName(...),');
    }

    public function testReducerOfAnUnnamedRuleIsNamedAfterIt(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');

        $parser = new ParserBuilder();
        $parser->addTokenReference('T_NUMBER')
            ->setReducer(new PhpCodeReducer('return 42;'));

        $code = (string) self::build($lexer, $parser);

        Assert::string($code)
            ->contains('function reduceRule0(')
            ->contains('0 => self::reduceRule0(...),');
    }

    public function testReducersOfTheSameNameAreToldApart(): void
    {
        $reducer = new PhpCodeReducer('return 42;');

        $names = (new PhpCodePrinter())->createMethodNames(
            reducers: [0 => $reducer, 1 => $reducer],
            constants: ['The Number' => 0, 'TheNumber' => 1],
        );

        Assert::same($names, [0 => 'reduceTheNumber', 1 => 'reduceTheNumber1']);
    }

    public function testCallableReducerIsReported(): void
    {
        Expect::exception(UnsupportedReducerException::class)
        ->withMessageContaining('The rule #0 is reduced by');

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');

        $parser = new ParserBuilder();
        $parser->addTokenReference('T_NUMBER')
            ->setName('Number')
            ->setReducer(new CallableReducer(static fn(): int => 42));

        (string) self::build($lexer, $parser);
    }

    public function testRuntimeEmbeddedLexerIsReported(): void
    {
        Expect::exception(UnsupportedEmbeddedLexerException::class)
        ->withMessageContaining('The fragment "php" is read by');

        $embedded = new LexerBuilder();
        $embedded->addPattern('\s++', 'T_WHITESPACE');

        $lexer = new LexerBuilder();
        $lexer->addPattern('<\?php', 'T_OPEN_TAG')
            ->enter('php');
        $lexer->addEmbeddedLexer('php', new RuntimeEmbeddedLexer($embedded->build()->toLexer()));

        $parser = new ParserBuilder();
        $parser->addTokenReference('T_OPEN_TAG')
            ->setName('Php');

        (string) self::build($lexer, $parser);
    }

    public function testUnsupportedValueIsReported(): void
    {
        Expect::exception(\ValueError::class)
        ->withMessageContaining('A value of type stdClass cannot be generated');

        (new PhpCodePrinter())->printValue(new \stdClass());
    }

    public function testGeneratedCodeIsSaved(): void
    {
        $pathname = $this->createPathname();

        $output = $this->compile('grammar.pp2')
            ->generate()
            ->save($pathname);

        Assert::true(\is_file($pathname));
        Assert::same(\file_get_contents($pathname), (string) $output);
    }

    public function testNamedParserIsDeclared(): void
    {
        $class = 'GeneratedParser' . \bin2hex(\random_bytes(8));

        $pathname = $this->createPathname();

        $this->compile('grammar.pp2')
            ->generate()
            ->withClassName($class)
            ->save($pathname);

        $code = (string) \file_get_contents($pathname);

        Assert::string($code)
            ->contains(\sprintf(
                "class %s implements \\Phplrt\\Contracts\\Parser\\ParserInterface\n{\n",
                $class,
            ))
            ->notContains('return new class');

        require $pathname;

        $parser = new $class();

        Assert::instanceOf($parser, ParserInterface::class);
        Assert::same($parser->parse(StringSource::createFromString('1 + 2 + 39')), 42);
    }

    public function testInvalidClassNameIsReported(): void
    {
        Expect::exception(InvalidClassNameException::class)
        ->withMessageContaining('The parser cannot be declared as "App\\Parser"');

        (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('App\\Parser');
    }

    public function testReadonlyIsAnnotatedBelowPhp82(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('LanguageParser')
            ->withTargetPhpVersion(TargetPhpVersion::Php81);

        Assert::string($code)
            ->contains(" * @readonly\n */\nclass LanguageParser implements \\Phplrt\\Contracts\\Parser\\ParserInterface\n")
            ->notContains('readonly class');
    }

    #[DataSet([TargetPhpVersion::Php82], 'PHP 8.2')]
    #[DataSet([TargetPhpVersion::Php86], 'PHP 8.6')]
    public function testReadonlyIsDeclaredFromPhp82(TargetPhpVersion $php): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('LanguageParser')
            ->withTargetPhpVersion($php);

        Assert::string($code)
            ->contains("\nreadonly class LanguageParser implements \\Phplrt\\Contracts\\Parser\\ParserInterface\n")
            ->notContains('@readonly');
    }

    #[DataSet([TargetPhpVersion::Php81], 'PHP 8.1')]
    #[DataSet([TargetPhpVersion::Php82], 'PHP 8.2')]
    public function testReadonlyIsDropped(TargetPhpVersion $php): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('LanguageParser')
            ->withTargetPhpVersion($php)
            ->withReadonly(false);

        Assert::string($code)
            ->contains("\nclass LanguageParser implements \\Phplrt\\Contracts\\Parser\\ParserInterface\n")
            ->notContains('@readonly')
            ->notContains('readonly class');
    }

    #[DataSet([ClassModifier::Abstract, TargetPhpVersion::Php81, 'abstract class'], 'abstract on PHP 8.1')]
    #[DataSet([ClassModifier::Final, TargetPhpVersion::Php81, 'final class'], 'final on PHP 8.1')]
    #[DataSet([ClassModifier::Default, TargetPhpVersion::Php81, 'class'], 'default on PHP 8.1')]
    #[DataSet([ClassModifier::Abstract, TargetPhpVersion::Php82, 'abstract readonly class'], 'abstract on PHP 8.2')]
    #[DataSet([ClassModifier::Final, TargetPhpVersion::Php82, 'final readonly class'], 'final on PHP 8.2')]
    #[DataSet([ClassModifier::Default, TargetPhpVersion::Php82, 'readonly class'], 'default on PHP 8.2')]
    public function testParserIsDeclaredWithTheModifier(
        ClassModifier $modifier,
        TargetPhpVersion $php,
        string $declaration,
    ): void {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('LanguageParser')
            ->withTargetPhpVersion($php)
            ->withClassModifier($modifier);

        Assert::string($code)
            ->contains("\n" . $declaration . " LanguageParser implements \\Phplrt\\Contracts\\Parser\\ParserInterface\n");
    }

    public function testParserCarriesNoModifierByDefault(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('LanguageParser');

        Assert::string($code)
            ->notContains('abstract class')
            ->notContains('final class');
    }

    #[DataSet([TargetPhpVersion::Php82, 'return new class implements'], 'PHP 8.2')]
    #[DataSet([TargetPhpVersion::Php83, 'return new readonly class implements'], 'PHP 8.3')]
    public function testAnonymousParserIsDeclared(TargetPhpVersion $php, string $declaration): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withTargetPhpVersion($php);

        Assert::string($code)->contains($declaration);
    }

    #[DataSet([ClassModifier::Abstract, 'abstract'], 'abstract')]
    #[DataSet([ClassModifier::Final, 'final'], 'final')]
    public function testAnonymousParserWithAModifierIsReported(ClassModifier $modifier, string $keyword): void
    {
        Expect::exception(UnsupportedClassModifierException::class)
        ->withMessageContaining('An anonymous parser cannot be declared as ' . $keyword);

        (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassModifier($modifier);
    }

    private function generate(string $name): ParserInterface
    {
        $pathname = $this->createPathname();

        $this->compile($name)
            ->generate()
            ->save($pathname);

        $parser = require $pathname;

        Assert::instanceOf($parser, ParserInterface::class);

        return $parser;
    }

    private function compile(string $name): Compiler
    {
        return (new Compiler())
            ->load(FileSource::createFromPathname(__DIR__ . '/resources/' . $name));
    }

    private function generateOf(string $grammar): GeneratedOutput
    {
        return (new Compiler())
            ->load(StringSource::createFromString($grammar))
            ->generate();
    }

    private static function build(LexerBuilder $lexer, ParserBuilder $parser): GeneratedOutput
    {
        $result = $lexer->build();

        return new GeneratedOutput(new CompilerResult($result, $parser->build($result)));
    }

    private function createPathname(): string
    {
        $pathname = \sys_get_temp_dir() . '/phplrt-' . \bin2hex(\random_bytes(8)) . '.php';

        $this->files[] = $pathname;

        return $pathname;
    }
}
