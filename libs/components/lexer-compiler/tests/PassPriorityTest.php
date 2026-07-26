<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Tests;

use Phplrt\Compiler\Lexer\Compiler\LexerCompilerPassInterface;
use Phplrt\Compiler\Lexer\LexerBuilder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/lexer-compiler')]
final class PassPriorityTest extends TestCase
{
    #[TestDox('The passes are processed in the order of their priority')]
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

    #[TestDox('The passes of the same priority are processed in the order they have been registered')]
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

    #[TestDox('The default passes are registered under their own priorities')]
    public function testDefaultPriorities(): void
    {
        $lexer = new LexerBuilder();

        self::assertSame([
            LexerBuilder::PASS_PRIORITY_NORMALIZE,
            LexerBuilder::PASS_PRIORITY_CHECK,
        ], \array_keys($lexer->passes));
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

            public function process(LexerBuilder $builder): void
            {
                $this->order[] = $this->name;
            }
        };
    }
}
