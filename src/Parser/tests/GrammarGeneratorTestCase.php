<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Tests;

use Phplrt\Grammar\Concatenation;
use Phplrt\Grammar\Lexeme;
use Phplrt\Grammar\Repetition;
use Phplrt\Parser\Grammar;
use Phplrt\Parser\Tests\Stub\Rule;

/**
 * Class GrammarGeneratorTestCase
 */
class GrammarGeneratorTestCase extends TestCase
{
    /**
     * @return void
     */
    public function testNamedRuleReturnsName(): void
    {
        new Grammar(function () {
            $this->assertSame('Name', yield 'Name' => Rule::new());
        });
    }

    /**
     * @return void
     */
    public function testAnonymousRuleReturnsIndex(): void
    {
        new Grammar(function () {
            $this->assertSame(0, yield Rule::new());
            $this->assertSame(1, yield Rule::new());
        });
    }

    /**
     * @return void
     */
    public function testRuleUsage(): void
    {
        new Grammar(function () {
            $this->assertSame(0, yield Rule::new());
            $this->assertSame(0, yield 0);
        });
    }

    /**
     * @return void
     */
    public function testHelpers(): void
    {
        $generator = new Grammar(static function (Grammar $c) {
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
