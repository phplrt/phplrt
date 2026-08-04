<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Analysis\ChoicePredictionConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\KeptRuleConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\LookaheadConstructionParserAnalysisPass;
use Phplrt\Parser\Builder\Analysis\ParserAnalysisPassInterface;
use Phplrt\Parser\Builder\Analysis\ParserResultContext;
use Phplrt\Parser\Builder\Compiler\InitialRuleParserCompilerPass;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;
use Phplrt\Parser\Builder\ParserBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/parser-compiler')]
final class PassPriorityTest extends TestCase
{
    #[TestDox('The compiler passes are processed in the order of their priority')]
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
        ], $order);
    }

    #[TestDox('The compiler passes of the same priority are processed in the order they have been registered')]
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

    #[TestDox('The analysis passes are processed after every compiler pass, whatever its priority is')]
    public function testAnalysisOrder(): void
    {
        $order = [];

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_NUMBER'));

        $parser->addAnalysisPass(self::createAnalysisPass($order, 'first'));
        $parser->addAnalysisPass(self::createAnalysisPass($order, 'second'));
        $parser->addCompilerPass(
            self::createCompilerPass($order, 'compile'),
            \PHP_INT_MAX,
        );

        $parser->build(self::createLexerBuilder()->build());

        self::assertSame(['compile', 'first', 'second'], $order);
    }

    #[TestDox('The default compiler passes are registered under their own priorities')]
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
    }

    #[TestDox('The default analysis passes are registered in the order they depend on each other')]
    public function testDefaultAnalysisPasses(): void
    {
        $parser = new ParserBuilder();

        self::assertSame([
            LookaheadConstructionParserAnalysisPass::class,
            KeptRuleConstructionParserAnalysisPass::class,
            ChoicePredictionConstructionParserAnalysisPass::class,
        ], \array_map(
            static fn(ParserAnalysisPassInterface $pass): string => $pass::class,
            $parser->analysisPasses,
        ));
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

            public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
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

            public function process(ParserResultContext $context): void
            {
                $this->order[] = $this->name;
            }
        };
    }
}
