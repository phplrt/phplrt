<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

/**
 * Class MarkersCompiler
 */
class MarkersCompiler extends PCRECompiler
{
    /**
     * @var string
     */
    private const FORMAT_MARKER = '(?:(?:%s)(*MARK:%s))';

    /**
     * @var string
     */
    private const FORMAT_BODY = '\\G(?|%s)';

    /**
     * @param array $chunks
     * @return string
     */
    protected function buildTokens(array $chunks): string
    {
        return \sprintf(self::FORMAT_BODY, \implode('|', $chunks));
    }

    /**
     * @param string $name
     * @param string $pattern
     * @return string
     */
    protected function buildToken(string $name, string $pattern): string
    {
        return \sprintf(self::FORMAT_MARKER, $pattern, $name);
    }
}
