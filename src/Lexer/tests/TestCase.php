<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use PHPUnit\Framework\TestCase as BastTestCase;

/**
 * Class TestCase
 */
abstract class TestCase extends BastTestCase
{
    /**
     * @param iterable|TokenInterface[] $result
     * @return array|TokenInterface[]
     */
    protected function tokensOf(iterable $result): array
    {
        $actual = [];

        foreach ($result as $token) {
            $token->getBytes();
            $actual[] = $token;
        }

        return $actual;
    }
}
