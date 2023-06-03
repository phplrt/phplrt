<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests;

use Phplrt\Contracts\Lexer\TokenInterface;
use PHPUnit\Framework\TestCase as BastTestCase;

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
