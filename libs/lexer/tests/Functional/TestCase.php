<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Tests\Functional;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
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
