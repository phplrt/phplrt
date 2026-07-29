<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\InvalidClassNameException;
use Phplrt\Compiler\Exception\UnsupportedEmbeddedLexerException;
use Phplrt\Compiler\Exception\UnsupportedReducerException;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Compiler\Generator\PhpCodePrinter;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\RuntimeEmbeddedLexer;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Lexer\Token\TokenEmbedding;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\File;
use Phplrt\Source\Source;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class GeneratorTest extends TestCase
{
    /**
     * The files the generated code has been saved into.
     *
     * @var list<non-empty-string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $pathname) {
            @\unlink($pathname);
        }

        $this->files = [];
    }

    #[TestDox('The generated parser recognizes what the grammar says')]
    public function testGeneratedParserRecognizesTheGrammar(): void
    {
        $parser = $this->generate('grammar.pp2');

        self::assertSame(42, $parser->parse(new Source('1 + 2 + 39')));
    }

    #[TestDox('The generated lexer reads the fragments the grammar says')]
    public function testGeneratedLexerReadsTheFragments(): void
    {
        $parser = $this->generate('states.pp2');

        $result = $parser->parse(new Source('"hello",'));

        self::assertIsArray($result);
        self::assertInstanceOf(TokenEmbedding::class, $result[0]);
        self::assertSame(['T_TEXT', 'T_QUOTE_CLOSE'], \array_column($result[0]->children, 'name'));
    }

    #[TestDox('The generated code belongs to the given namespace')]
    public function testNamespaceIsGenerated(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withNamespaceName('Example\\Some');

        self::assertStringContainsString("\nnamespace Example\\Some;\n", $code);
    }

    #[TestDox('The generated code refers to the given classes by their short names')]
    public function testClassImportsAreGenerated(): void
    {
        $code = (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassImport('App\\Node')
            ->withClassImport('App\\Other\\Node', as: 'OtherNode');

        self::assertStringContainsString("\nuse App\\Node;\nuse App\\Other\\Node as OtherNode;\n", $code);
    }

    #[TestDox('A named token is referred to by the constant of the generated parser')]
    public function testNamedTokenIsReferredByConstant(): void
    {
        $code = (string) $this->compile('grammar.pp2')->generate();

        self::assertStringContainsString('public const int T_NUMBER = 0;', $code);
        self::assertStringContainsString('new \\Phplrt\\Parser\\Grammar\\Lexeme(self::T_NUMBER, true)', $code);
    }

    #[TestDox('A token written inside a rule is referred to by its identifier')]
    public function testInlineTokenIsReferredByIdentifier(): void
    {
        $code = (string) $this->compile('states.pp2')->generate();

        // The token is named by nothing, so there is no constant standing
        // for it
        self::assertStringContainsString('new \\Phplrt\\Parser\\Grammar\\Lexeme(2, false)', $code);
    }

    #[TestDox('A lexer reading a fragment is written down once and referred to')]
    public function testFragmentIsWrittenDownOnce(): void
    {
        $code = (string) $this->compile('states.pp2')->generate();

        self::assertStringContainsString('$state_string = new \\Phplrt\\Lexer\\Lexer(', $code);
        self::assertStringContainsString('0 => $state_string,', $code);
    }

    #[TestDox('A rule is reduced by a method named after it')]
    public function testReducerIsGeneratedAsAMethod(): void
    {
        $code = (string) $this->compile('grammar.pp2')->generate();

        self::assertStringContainsString(
            'private static function reduceExpression(\\Phplrt\\Parser\\Context $ctx, mixed $children): mixed',
            $code,
        );
        self::assertStringContainsString('0 => self::reduceExpression(...),', $code);
    }

    #[TestDox('A rule reduced by the parser itself is reduced by a method of its own')]
    public function testReducerOfTheParserIsNotStatic(): void
    {
        $code = (string) $this->generateOf(<<<'PP2'
            %token T_NAME [a-z]++

            Name -> { return $this->rename($children); } : <T_NAME> ;
            PP2);

        self::assertStringContainsString(
            'private function reduceName(\\Phplrt\\Parser\\Context $ctx, mixed $children): mixed',
            $code,
        );
        self::assertStringContainsString('0 => $this->reduceName(...),', $code);
    }

    #[TestDox('A rule named by nothing is reduced by a method named after its identifier')]
    public function testReducerOfAnUnnamedRuleIsNamedAfterIt(): void
    {
        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');

        $parser = new ParserBuilder();
        $parser->addTokenReference('T_NUMBER')
            ->setReducer(new PhpCodeReducer('return 42;'));

        $code = (string) self::build($lexer, $parser);

        self::assertStringContainsString('function reduceRule0(', $code);
        self::assertStringContainsString('0 => self::reduceRule0(...),', $code);
    }

    #[TestDox('Two rules spelled the same way are reduced by methods of their own')]
    public function testReducersOfTheSameNameAreToldApart(): void
    {
        $reducer = new PhpCodeReducer('return 42;');

        $names = new PhpCodePrinter()->createMethodNames(
            reducers: [0 => $reducer, 1 => $reducer],
            constants: ['The Number' => 0, 'TheNumber' => 1],
        );

        self::assertSame([0 => 'reduceTheNumber', 1 => 'reduceTheNumber1'], $names);
    }

    #[TestDox('A rule reduced by a callback is reported')]
    public function testCallableReducerIsReported(): void
    {
        $this->expectException(UnsupportedReducerException::class);
        $this->expectExceptionMessage('The rule #0 is reduced by');

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++', 'T_NUMBER');

        $parser = new ParserBuilder();
        $parser->addTokenReference('T_NUMBER')
            ->setName('Number')
            ->setReducer(new CallableReducer(static fn(): int => 42));

        (string) self::build($lexer, $parser);
    }

    #[TestDox('A fragment read by a lexer built at runtime is reported')]
    public function testRuntimeEmbeddedLexerIsReported(): void
    {
        $this->expectException(UnsupportedEmbeddedLexerException::class);
        $this->expectExceptionMessage('The fragment "php" is read by');

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

    #[TestDox('The generated code is saved into the given file')]
    public function testGeneratedCodeIsSaved(): void
    {
        $pathname = $this->createPathname();

        $output = $this->compile('grammar.pp2')
            ->generate()
            ->save($pathname);

        self::assertFileExists($pathname);
        self::assertSame((string) $output, \file_get_contents($pathname));
    }

    #[TestDox('A parser that is named is declared instead of being returned')]
    public function testNamedParserIsDeclared(): void
    {
        $class = 'GeneratedParser' . \bin2hex(\random_bytes(8));

        $pathname = $this->createPathname();

        $this->compile('grammar.pp2')
            ->generate()
            ->save($pathname, $class);

        $code = (string) \file_get_contents($pathname);

        self::assertStringContainsString(\sprintf(
            "readonly class %s extends \\Phplrt\\Parser\\Parser\n{\n",
            $class,
        ), $code);
        self::assertStringNotContainsString('return new readonly class', $code);

        require $pathname;

        $parser = new $class();

        self::assertInstanceOf(ParserInterface::class, $parser);
        self::assertSame(42, $parser->parse(new Source('1 + 2 + 39')));
    }

    #[TestDox('A parser named the way no class may be named is reported')]
    public function testInvalidClassNameIsReported(): void
    {
        $this->expectException(InvalidClassNameException::class);
        $this->expectExceptionMessage('The parser cannot be declared as "App\\Parser"');

        (string) $this->compile('grammar.pp2')
            ->generate()
            ->withClassName('App\\Parser');
    }

    /**
     * Reads the grammar of the given file and runs the parser generated of it.
     *
     * @param non-empty-string $name
     */
    private function generate(string $name): ParserInterface
    {
        $pathname = $this->createPathname();

        $this->compile($name)
            ->generate()
            ->save($pathname);

        $parser = require $pathname;

        self::assertInstanceOf(ParserInterface::class, $parser);

        return $parser;
    }

    /**
     * @param non-empty-string $name
     */
    private function compile(string $name): Compiler
    {
        return new Compiler()
            ->load(new File(__DIR__ . '/resources/' . $name));
    }

    private function generateOf(string $grammar): GeneratedOutput
    {
        return new Compiler()
            ->load(new Source($grammar))
            ->generate();
    }

    private static function build(LexerBuilder $lexer, ParserBuilder $parser): GeneratedOutput
    {
        $result = $lexer->build();

        return new GeneratedOutput(new CompilerResult($result, $parser->build($result)));
    }

    /**
     * @return non-empty-string
     */
    private function createPathname(): string
    {
        $pathname = \sys_get_temp_dir() . '/phplrt-' . \bin2hex(\random_bytes(8)) . '.php';

        $this->files[] = $pathname;

        return $pathname;
    }
}
