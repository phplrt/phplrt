<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Parser\Tests;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysis;
use Phplrt\Compiler\Parser\Analysis\ParserAnalysisPassInterface;
use Phplrt\Compiler\Parser\Compiler\InitialRuleParserCompilerPass;
use Phplrt\Compiler\Parser\Compiler\ParserCompilerPassInterface;
use Phplrt\Compiler\Parser\ParserBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class PassPriorityTest extends TestCase
{
    #[TestDox('The passes are processed in the order of their priority')]
    public function testPriorityOrder(): void
    {
        $order = [];

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_NUMBER'));

        $parser->addCompilerPass(
            self::createCompilerPass($order, 'optimize'),
            ParserBuilder::PASS_PRIORITY_OPTIMIZE,
        );
        $parser->addCompilerPass(
            self::createCompilerPass($order, 'normalize'),
            ParserBuilder::PASS_PRIORITY_NORMALIZE,
        );
        $parser->addAnalysisPass(self::createAnalysisPass($order, 'analyze'));
        $parser->addCompilerPass(
            self::createCompilerPass($order, 'check-after-optimize'),
            ParserBuilder::PASS_PRIORITY_CHECK_AFTER_OPTIMIZE,
        );
        $parser->addCompilerPass(self::createCompilerPass($order, 'check'));

        /**
         * The priority has not been used yet, so the pass is appended to the
         * list and may only be processed first while the list is sorted.
         */
        $parser->addCompilerPass(
            self::createCompilerPass($order, 'custom'),
            ParserBuilder::PASS_PRIORITY_NORMALIZE - 1,
        );

        $parser->build(self::createLexerBuilder()->build());

        self::assertSame([
            'custom',
            'normalize',
            'check',
            'optimize',
            'check-after-optimize',
            'analyze',
        ], $order);
    }

    #[TestDox('The passes of the same priority are processed in the order they have been registered')]
    public function testRegistrationOrder(): void
    {
        $order = [];

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_NUMBER'));

        $parser->addCompilerPass(self::createCompilerPass($order, 'first'));
        $parser->addCompilerPass(self::createCompilerPass($order, 'second'));

        $parser->build(self::createLexerBuilder()->build());

        self::assertSame(['first', 'second'], $order);
    }

    #[TestDox('The default passes are registered under their own priorities')]
    public function testDefaultPriorities(): void
    {
        $parser = new ParserBuilder();

        self::assertSame([
            ParserBuilder::PASS_PRIORITY_NORMALIZE,
            ParserBuilder::PASS_PRIORITY_CHECK,
            ParserBuilder::PASS_PRIORITY_OPTIMIZE,
        ], \array_keys($parser->compilerPasses));

        self::assertInstanceOf(
            InitialRuleParserCompilerPass::class,
            $parser->compilerPasses[ParserBuilder::PASS_PRIORITY_NORMALIZE][0],
            'The initial rule is computed before everything that needs it',
        );

        self::assertSame(
            [ParserBuilder::PASS_PRIORITY_ANALYZE],
            \array_keys($parser->analysisPasses),
        );
    }

    /**
     * @param list<string> $order
     * @param non-empty-string $name
     */
    private static function createCompilerPass(array &$order, string $name): ParserCompilerPassInterface
    {
        return new class ($order, $name) implements ParserCompilerPassInterface {
            /**
             * @param list<string> $order
             * @param non-empty-string $name
             */
            public function __construct(
                private array &$order,
                private readonly string $name,
            ) {}

            public function process(ParserBuilder $builder, LexerBuilderResult $lexer): void
            {
                $this->order[] = $this->name;
            }
        };
    }

    /**
     * @param list<string> $order
     * @param non-empty-string $name
     */
    private static function createAnalysisPass(array &$order, string $name): ParserAnalysisPassInterface
    {
        return new class ($order, $name) implements ParserAnalysisPassInterface {
            /**
             * @param list<string> $order
             * @param non-empty-string $name
             */
            public function __construct(
                private array &$order,
                private readonly string $name,
            ) {}

            public function process(ParserAnalysis $analysis): void
            {
                $this->order[] = $this->name;
            }
        };
    }
}
