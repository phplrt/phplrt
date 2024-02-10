<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Functional;

use Phplrt\Parser\Grammar\Builder;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Tests\Functional\Stub\Rule;

class GrammarGeneratorTest extends TestCase
{
    public function testNamedRuleReturnsName(): void
    {
        new Builder(function () {
            $this->assertSame('Name', yield 'Name' => Rule::new());
        });
    }

    public function testAnonymousRuleReturnsIndex(): void
    {
        new Builder(function () {
            $this->assertSame(0, yield Rule::new());
            $this->assertSame(1, yield Rule::new());
        });
    }

    public function testRuleUsage(): void
    {
        new Builder(function () {
            $this->assertSame(0, yield Rule::new());
            $this->assertSame(0, yield 0);
        });
    }

    public function testHelpers(): void
    {
        $generator = new Builder(static function (Builder $c) {
            yield 'sum' => $c->concat(
                $digit = yield $c->token('digit'),
                yield $c->repeat(
                    yield $c->token('plus'),
                    yield $digit
                )
            );
        });

        $expected = [
            0     => new Lexeme('digit'),
            1     => new Lexeme('plus'),
            2     => new Concatenation([1, 0]),
            3     => new Repetition(2),
            'sum' => new Concatenation([0, 3]),
        ];

        $this->assertEquals($expected, \iterator_to_array($generator));
    }
}
