<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Lexer;

use Phplrt\Lexer\Driver\ParleLexer;

/**
 * Class ParleTestCase
 */
class ParleTestCase extends LexerTestCase
{
    /**
     * @return array
     * @throws \Phplrt\Lexer\Exception\BadLexemeException
     * @throws \PHPUnit\Framework\SkippedTestError
     */
    public function provider(): array
    {
        if (! \class_exists(\Parle\Lexer::class, false)) {
            $this->markTestSkipped('Parle extension not installed');
        }

        return [
            [new ParleLexer(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE'])],
        ];
    }
}
