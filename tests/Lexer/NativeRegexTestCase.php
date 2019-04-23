<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Lexer;

use Phplrt\Lexer\Driver\NativeRegex;

/**
 * Class NativeCompilerTestCase
 */
class NativeRegexTestCase extends LexerTestCase
{
    /**
     * @return array
     */
    public function provider(): array
    {
        return [
            [new NativeRegex(['T_WHITESPACE' => '\s+', 'T_DIGIT' => '\d+'], ['T_WHITESPACE'])],
        ];
    }
}
