<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Internal\Regex\MarkersCompiler as MarkersCompiler;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Lexer\Token\Token;

class Markers extends Driver
{
    /**
     * @var string
     */
    private const UNKNOWN_PATTERN = '.+?';

    /**
     * Markers constructor.
     *
     * @param MarkersCompiler|null $compiler
     */
    public function __construct(MarkersCompiler $compiler = null)
    {
        parent::__construct($compiler ?? new MarkersCompiler());
    }

    /**
     * @param array $tokens
     * @param ReadableInterface $source
     * @param int $offset
     * @return iterable<TokenInterface>
     */
    public function run(array $tokens, ReadableInterface $source, int $offset = 0): iterable
    {
        $pattern = $this->getPattern(\array_merge($tokens, [
            self::UNKNOWN_TOKEN_NAME => self::UNKNOWN_PATTERN,
        ]));

        $result = $this->match($pattern, $source->getContents(), $offset);

        /** @var array<string> $payload */
        foreach ($result as $payload) {
            $name = \array_pop($payload);

            if (\count($payload) > 1) {
                $tokens = [];

                foreach ($payload as $value) {
                    $tokens[] = new Token($name, $value, $offset);
                }

                yield Composite::fromArray($tokens);
            } else {
                yield new Token($name, $payload[0], $offset);
            }

            $offset += \strlen($payload[0]);
        }
    }

    private function make(string $name, array $values, int $offset): TokenInterface
    {
        if (\count($values) > 1) {
            $tokens = [];

            foreach ($values as $value) {
                $tokens[] = new Token($name, $value, $offset);
            }

            return Composite::fromArray($tokens);
        }

        return new Token($name, $values[0], $offset);
    }

    /**
     * @param string $pattern
     * @param string $source
     * @param int $offset
     * @return array
     */
    private function match(string $pattern, string $source, int $offset): array
    {
        \preg_match_all($pattern, $source, $matches, \PREG_SET_ORDER, $offset);

        return $matches;
    }
}
