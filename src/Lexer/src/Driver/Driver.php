<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Lexer\Exception\LexerException;

/**
 * Class Driver
 */
abstract class Driver implements DriverInterface
{
    /**
     * @var array|string[]
     */
    protected $tokens;

    /**
     * @var mixed[]
     */
    protected $map = [];

    /**
     * State constructor.
     *
     * @param array|string[] $tokens
     */
    public function __construct(array $tokens)
    {
        foreach ($tokens as $name => $pattern) {
            $pattern = (string)$pattern;

            $this->tokens[$this->createName($name, $pattern)] = $pattern;
        }
    }

    /**
     * @param mixed $name
     * @param string $pattern
     * @return string
     */
    private function createName($name, string $pattern): string
    {
        if (\is_string($name) && $name !== '') {
            return $name;
        }

        if (\is_int($name) || $name === '') {
            $this->map[$alias = $this->generate($pattern)] = $name;

            return $alias;
        }

        throw new LexerException('Type ' . \gettype($name) . ' can not be used as token name');
    }

    /**
     * @param string $token
     * @return mixed|string
     */
    protected function name(string $token)
    {
        return $this->map[$token] ?? $token;
    }

    /**
     * @param string $pattern
     * @return string
     */
    private function generate(string $pattern): string
    {
        return 'T' . \hash('crc32', $pattern);
    }
}
