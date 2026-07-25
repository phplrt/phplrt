<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Tracing\Result\Failure;

/**
 * Collects the furthest point the input failed to match, for error reporting.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal
 *
 * @template-implements CompletableInterface<Failure>
 */
final class ErrorReport implements CompletableInterface
{
    /**
     * @var int<-1, max>
     */
    private int $furthest = -1;

    private ?TokenInterface $token = null;

    /**
     * @var array<int, int>
     */
    private array $expected = [];

    public function __construct(
        private readonly BufferInterface $buffer,
    ) {}

    public function record(int $expectedTokenId): void
    {
        $buffer = $this->buffer;
        $position = $buffer->key;
        $furthest = $this->furthest;

        if ($position > $furthest) {
            $this->furthest = $position;
            $this->token = $buffer->current;
            $this->expected = [$expectedTokenId => $expectedTokenId];

            return;
        }

        if ($position === $furthest) {
            $this->expected[$expectedTokenId] = $expectedTokenId;
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
            expected: \array_values($this->expected),
        );
    }
}
