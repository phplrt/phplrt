<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\Result\Failure;

/**
 * Collects the furthest point the input failed to match, for error reporting.
 *
 * What the grammar could read instead is not collected at all: the tokens a
 * rule may begin with are known before the recognition starts, so the rules
 * that have failed are remembered by their identifiers and the tokens are
 * looked up once, after the recognition is over.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal
 */
final class ErrorReport
{
    /**
     * How many of the rules failing at the same position are remembered.
     *
     * A rule rejected before it is entered is rejected by the tokens it may
     * begin with, and those of a rule containing it are the tokens of all the
     * rules it contains, so the outermost failure alone usually tells the whole
     * story. The rest are only worth remembering to the extent an error message
     * can show them.
     *
     * @var int<1, max>
     */
    private const int RULES_LIMIT = 4;

    /**
     * The position of the failure the report describes.
     *
     * @var int<-1, max>
     */
    public private(set) int $furthest = -1;

    private ?TokenInterface $token = null;

    /**
     * The rules that have failed at the position the report describes.
     *
     * @var list<int>
     */
    private array $rules = [];

    /**
     * @param list<RuleInterface> $grammar
     * @param array<int, array<int, true>> $startTokens
     */
    public function __construct(
        private readonly BufferInterface $buffer,
        private readonly array $grammar,
        private readonly array $startTokens,
    ) {}

    /**
     * Remembers that the given rule could not be recognized at the position the
     * input has been read up to.
     */
    public function record(int $rule): void
    {
        $buffer = $this->buffer;
        $position = $buffer->key;
        $furthest = $this->furthest;

        if ($position > $furthest) {
            $this->furthest = $position;
            $this->token = $buffer->current;
            $this->rules = [$rule];

            return;
        }

        // The limit is reached by the presence of its last element, so the
        // recognition counts nothing on its way
        if ($position === $furthest && !isset($this->rules[self::RULES_LIMIT - 1])) {
            $this->rules[] = $rule;
        }
    }

    public function finish(): Failure
    {
        $buffer = $this->buffer;

        // The input the analysis stopped at is reported in case no deeper
        // failure has been recorded
        if ($this->token === null || $this->furthest < $buffer->key) {
            return new Failure($buffer->current);
        }

        return new Failure(
            token: $this->token,
            expected: $this->calculateExpectedTokens(),
        );
    }

    /**
     * @return list<int>
     */
    private function calculateExpectedTokens(): array
    {
        $result = [];

        foreach ($this->rules as $rule) {
            $expected = $this->startTokens[$rule] ?? null;

            if ($expected !== null) {
                $result += $expected;

                continue;
            }

            /**
             * A grammar that has been given no lookahead table is recognized
             * the same way, so the terminals it has failed on are read off the
             * rules themselves.
             */
            $definition = $this->grammar[$rule] ?? null;

            if ($definition instanceof Lexeme) {
                $result[$definition->tokenId] = true;
            }
        }

        \ksort($result);

        return \array_keys($result);
    }
}
