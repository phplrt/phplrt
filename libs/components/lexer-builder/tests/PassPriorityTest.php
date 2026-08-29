<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Analysis\ChannelConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\LexerAnalysisPassInterface;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Analysis\RegexConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\SubgroupConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TokenNameConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TransitionConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/lexer-compiler')]
#[Test]
final class PassPriorityTest extends TestCase
{
    public function testPriorityOrder(): void
    {
        $order = [];

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++');

        $lexer->addCompilerPass(
            self::createPass($order, 'optimize'),
            LexerBuilder::PASS_PRIORITY_OPTIMIZE,
        );
        $lexer->addCompilerPass(
            self::createPass($order, 'normalize'),
            LexerBuilder::PASS_PRIORITY_NORMALIZE,
        );
        $lexer->addCompilerPass(
            self::createPass($order, 'check-after-optimize'),
            LexerBuilder::PASS_PRIORITY_CHECK_AFTER_OPTIMIZE,
        );
        $lexer->addCompilerPass(self::createPass($order, 'check'));

        $lexer->addCompilerPass(
            self::createPass($order, 'custom'),
            LexerBuilder::PASS_PRIORITY_NORMALIZE - 1,
        );

        $lexer->build();

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

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++');

        $lexer->addCompilerPass(self::createPass($order, 'first'));
        $lexer->addCompilerPass(self::createPass($order, 'second'));

        $lexer->build();

        Assert::same($order, ['first', 'second']);
    }

    public function testAnalysisOrder(): void
    {
        $order = [];

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++');

        $lexer->addAnalysisPass(self::createAnalysisPass($order, 'first'));
        $lexer->addAnalysisPass(self::createAnalysisPass($order, 'second'));
        $lexer->addCompilerPass(self::createPass($order, 'compile'), \PHP_INT_MAX);

        $lexer->build();

        Assert::same($order, ['compile', 'first', 'second']);
    }

    public function testDefaultPriorities(): void
    {
        $lexer = new LexerBuilder();

        Assert::same(\array_keys($lexer->compilerPasses), [
            LexerBuilder::PASS_PRIORITY_NORMALIZE,
            LexerBuilder::PASS_PRIORITY_CHECK,
        ]);
    }

    public function testDefaultAnalysisPasses(): void
    {
        $lexer = new LexerBuilder();

        Assert::same(\array_map(
            static fn(LexerAnalysisPassInterface $pass): string => $pass::class,
            $lexer->analysisPasses,
        ), [
            TokenNameConstructionLexerAnalysisPass::class,
            ChannelConstructionLexerAnalysisPass::class,
            TransitionConstructionLexerAnalysisPass::class,
            SubgroupConstructionLexerAnalysisPass::class,
            RegexConstructionLexerAnalysisPass::class,
        ]);
    }

    private static function createPass(array &$order, string $name): LexerCompilerPassInterface
    {
        return new class ($order, $name) implements LexerCompilerPassInterface {
            public function __construct(
                private array &$order,
                private readonly string $name,
            ) {}

            public function process(LexerBuildingContext $context): void
            {
                $this->order[] = $this->name;
            }
        };
    }

    private static function createAnalysisPass(array &$order, string $name): LexerAnalysisPassInterface
    {
        return new class ($order, $name) implements LexerAnalysisPassInterface {
            public function __construct(
                private array &$order,
                private readonly string $name,
            ) {}

            public function process(LexerResultContext $context): void
            {
                $this->order[] = $this->name;
            }
        };
    }
}
