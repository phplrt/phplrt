<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\Result\FailureTracingResult;

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
 *
 * @phpstan-import-type LookaheadTableType from GrammarTable
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
     * The rule describing the failure by a message of its own, or {@see null}
     * in case no such rule was being recognized when the input has broken.
     */
    private ?int $labelled = null;

    /**
     * The position the rule above has been entered at.
     *
     * @var int<-1, max>
     */
    private int $labelledAt = -1;

    /**
     * The rule the recognition starts at, in case it describes the failure by
     * a message of its own, or {@see null} in case it describes the failure by
     * nothing.
     *
     * The rule is contained by nothing, so it stands for the source as a whole
     * and describes every failure the rules inside it leave undescribed, no
     * matter where the reading has stopped.
     */
    private ?int $initial = null;

    public function __construct(
        private readonly BufferInterface $buffer,
        /**
         * @var list<RuleInterface>
         */
        private readonly array $grammar,
        /**
         * @var LookaheadTableType
         */
        private readonly array $lookahead,
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

            // The rules remembered so far were being recognized when the input
            // broke somewhere before this, which is no longer the error the
            // report describes
            $this->labelled = null;
            $this->labelledAt = -1;

            return;
        }

        // The limit is reached by the presence of its last element, so the
        // recognition counts nothing on its way
        if ($position === $furthest && !isset($this->rules[self::RULES_LIMIT - 1])) {
            $this->rules[] = $rule;
        }
    }

    /**
     * Remembers that the given rule, which describes the failure by a message
     * of its own, could not be recognized.
     *
     * The rule is reported once the input has been given back, so the reading
     * is at the position the rule has been entered at rather than at the one
     * it has broken on.
     */
    public function label(int $rule): void
    {
        $position = $this->buffer->key;

        /**
         * A rule entered past the deepest failure has never reached it, so it
         * describes an error earlier than the one that has stopped the reading.
         *
         * Of the rules that have reached it, the one entered last is the one
         * written closest to the input that has broken, and the rules are
         * reported as the recognition unwinds, innermost first, so the one
         * remembered at the same position is already that one.
         */
        if ($position > $this->furthest || $position <= $this->labelledAt) {
            return;
        }

        $this->labelledAt = $position;
        $this->labelled = $rule;
    }

    /**
     * Remembers that the rule the recognition starts at, which describes the
     * failure by a message of its own, has not described the source.
     */
    public function labelInitial(int $rule): void
    {
        $this->initial = $rule;
    }

    /**
     * Describes the reading that has stopped at the given token, along with
     * everything it has managed to read.
     *
     * @param array<int<0, max>, int|TokenInterface> $entries
     * @param int<0, max> $length
     */
    public function toFailureResult(TokenInterface $stoppedAt, array $entries = [], int $length = 0): FailureTracingResult
    {
        $buffer = $this->buffer;

        // The input the analysis stopped at is reported in case no deeper
        // failure has been recorded
        if ($this->token === null || $this->furthest < $buffer->key) {
            /**
             * The rules remembered so far were being recognized when the input
             * broke before the place the reading has stopped at, so what they
             * say is said about another failure than the one reported here.
             */
            return new FailureTracingResult(
                stoppedAt: $stoppedAt,
                token: $buffer->current,
                labelled: $this->initial,
                entries: $entries,
                length: $length,
            );
        }

        return new FailureTracingResult(
            stoppedAt: $stoppedAt,
            token: $this->token,
            expected: $this->calculateExpectedTokens(),
            labelled: $this->labelled ?? $this->initial,
            entries: $entries,
            length: $length,
        );
    }

    /**
     * @return list<int>
     */
    private function calculateExpectedTokens(): array
    {
        $result = [];

        foreach ($this->rules as $rule) {
            $expected = $this->lookahead[$rule] ?? null;

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
