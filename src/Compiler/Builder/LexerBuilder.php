<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

/**
 * Class LexerBuilder
 */
class LexerBuilder
{
    /**
     * @var string
     */
    public const DEFAULT_STATE = 'default';

    /**
     * @var int
     */
    private const I_TOKENS = 0x00;

    /**
     * @var int
     */
    private const I_SKIPS = 0x01;

    /**
     * @var int
     */
    private const I_JUMPS = 0x02;

    /**
     * @var int
     */
    private const I_BREAKS = 0x03;

    /**
     * @var array|TokenDefinition[]
     */
    private $tokens = [];

    /**
     * @param string $pattern
     * @param string|null $name
     * @return TokenDefinition
     */
    public function skip(string $pattern, string $name = null): TokenDefinition
    {
        return $this->token($pattern, $name)->skip();
    }

    /**
     * @param string $pattern
     * @param string|null $name
     * @return TokenDefinition
     */
    public function token(string $pattern, string $name = null): TokenDefinition
    {
        $token = $this->tokens[] = new TokenDefinition($pattern);

        if ($name !== null) {
            $token->as($name);
        }

        return $token;
    }

    /**
     * @return array
     */
    public function make(): array
    {
        $result = $names = [];
        $states = [self::DEFAULT_STATE => 0];

        foreach ($this->tokens as $token) {
            // Collect all states
            $stateId = $this->stateId($states, $token->state);

            // Collect token name
            $tokenId = $this->tokenId($names, $token->as);

            // Boot state
            if (! isset($result[$stateId])) {
                $result[$stateId] = [
                    self::I_TOKENS => [],
                    self::I_SKIPS  => [],
                    self::I_JUMPS  => [],
                    self::I_BREAKS => [],
                ];
            }

            $result[$stateId][self::I_TOKENS][$tokenId] = $token->pattern;

            if ($token->skip) {
                $result[$stateId][self::I_SKIPS][] = $tokenId;
            }

            if ($token->goesTo !== null) {
                $result[$stateId][self::I_JUMPS][$tokenId] = $this->stateId($states, $token->goesTo);
            }

            if ($token->causes !== null) {
                $result[$stateId][self::I_BREAKS][$tokenId] = $this->stateId($states, $token->causes);
            }
        }

        return $result;
    }

    /**
     * @param array $states
     * @param string|null $state
     * @return int
     */
    private function stateId(array &$states, ?string $state): int
    {
        $state = $state ?? self::DEFAULT_STATE;

        return $states[$state] ?? $states[$state] = \count($states);
    }

    /**
     * @param array $names
     * @param string|null $name
     * @return int
     */
    private function tokenId(array &$names, ?string $name): int
    {
        return $names[$name ?? \count($names)] = \count($names);
    }
}
