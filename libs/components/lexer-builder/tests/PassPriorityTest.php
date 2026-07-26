<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Tests;

use Phplrt\Lexer\Builder\Analysis\ChannelConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\LexerAnalysisPassInterface;
use Phplrt\Lexer\Builder\Analysis\RegexConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TokenNameConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\TransitionConstructionLexerAnalysisPass;
use Phplrt\Lexer\Builder\Analysis\LexerResultContext;
use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class PassPriorityTest extends TestCase
{
    #[TestDox('The compiler passes are processed in the order of their priority')]
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

        /**
         * The priority has not been used yet, so the pass is appended to the
         * list and may only be processed first while the list is sorted.
         */
        $lexer->addCompilerPass(
            self::createPass($order, 'custom'),
            LexerBuilder::PASS_PRIORITY_NORMALIZE - 1,
        );

        $lexer->build();

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

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++');

        $lexer->addCompilerPass(self::createPass($order, 'first'));
        $lexer->addCompilerPass(self::createPass($order, 'second'));

        $lexer->build();

        self::assertSame(['first', 'second'], $order);
    }

    #[TestDox('The analysis passes are processed after every compiler pass, whatever its priority is')]
    public function testAnalysisOrder(): void
    {
        $order = [];

        $lexer = new LexerBuilder();
        $lexer->addPattern('\d++');

        $lexer->addAnalysisPass(self::createAnalysisPass($order, 'first'));
        $lexer->addAnalysisPass(self::createAnalysisPass($order, 'second'));
        $lexer->addCompilerPass(self::createPass($order, 'compile'), \PHP_INT_MAX);

        $lexer->build();

        self::assertSame(['compile', 'first', 'second'], $order);
    }

    #[TestDox('The default compiler passes are registered under their own priorities')]
    public function testDefaultPriorities(): void
    {
        $lexer = new LexerBuilder();

        self::assertSame([
            LexerBuilder::PASS_PRIORITY_NORMALIZE,
            LexerBuilder::PASS_PRIORITY_CHECK,
        ], \array_keys($lexer->compilerPasses));
    }

    #[TestDox('Everything derived from the token definitions is described by an analysis pass')]
    public function testDefaultAnalysisPasses(): void
    {
        $lexer = new LexerBuilder();

        self::assertSame([
            TokenNameConstructionLexerAnalysisPass::class,
            ChannelConstructionLexerAnalysisPass::class,
            TransitionConstructionLexerAnalysisPass::class,
            RegexConstructionLexerAnalysisPass::class,
        ], \array_map(
            static fn(LexerAnalysisPassInterface $pass): string => $pass::class,
            $lexer->analysisPasses,
        ));
    }

    /**
     * @param list<string> $order
     * @param non-empty-string $name
     */
    private static function createPass(array &$order, string $name): LexerCompilerPassInterface
    {
        return new class ($order, $name) implements LexerCompilerPassInterface {
            /**
             * @param list<string> $order
             * @param non-empty-string $name
             */
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

    /**
     * @param list<string> $order
     * @param non-empty-string $name
     */
    private static function createAnalysisPass(array &$order, string $name): LexerAnalysisPassInterface
    {
        return new class ($order, $name) implements LexerAnalysisPassInterface {
            /**
             * @param list<string> $order
             * @param non-empty-string $name
             */
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
