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
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/parser-compiler')]
#[Test]
final class PassPriorityTest extends TestCase
{
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

        $parser->addCompilerPass(
            self::createCompilerPass($order, 'custom'),
            ParserBuilder::PASS_PRIORITY_NORMALIZE - 1,
        );

        $parser->build(self::createLexerBuilder()->build());

        Assert::same($order, [
            'custom',
            'normalize',
            'check',
            'optimize',
            'check-after-optimize',
        ]);
    }

    public function testRegistrationOrder(): void
    {
        $order = [];

        $parser = new ParserBuilder();
        $parser->setInitialRule($parser->addTokenReference('T_NUMBER'));

        $parser->addCompilerPass(self::createCompilerPass($order, 'first'));
        $parser->addCompilerPass(self::createCompilerPass($order, 'second'));

        $parser->build(self::createLexerBuilder()->build());

        Assert::same($order, ['first', 'second']);
    }

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

        Assert::same($order, ['compile', 'first', 'second']);
    }

    public function testDefaultPriorities(): void
    {
        $parser = new ParserBuilder();

        Assert::same(\array_keys($parser->compilerPasses), [
            ParserBuilder::PASS_PRIORITY_NORMALIZE,
            ParserBuilder::PASS_PRIORITY_CHECK,
            ParserBuilder::PASS_PRIORITY_OPTIMIZE,
            ParserBuilder::PASS_PRIORITY_CHECK_AFTER_OPTIMIZE,
        ]);

        Assert::instanceOf($parser->compilerPasses[ParserBuilder::PASS_PRIORITY_NORMALIZE][0], InitialRuleParserCompilerPass::class, 'The initial rule is computed before everything that needs it');
    }

    public function testDefaultAnalysisPasses(): void
    {
        $parser = new ParserBuilder();

        Assert::same(\array_map(
            static fn(ParserAnalysisPassInterface $pass): string => $pass::class,
            $parser->analysisPasses,
        ), [
            LookaheadConstructionParserAnalysisPass::class,
            KeptRuleConstructionParserAnalysisPass::class,
            ChoicePredictionConstructionParserAnalysisPass::class,
        ]);
    }

    private static function createCompilerPass(array &$order, string $name): ParserCompilerPassInterface
    {
        return new class ($order, $name) implements ParserCompilerPassInterface {
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

    private static function createAnalysisPass(array &$order, string $name): ParserAnalysisPassInterface
    {
        return new class ($order, $name) implements ParserAnalysisPassInterface {
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
