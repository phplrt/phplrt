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
        $position = $this->buffer->key;

        if ($position > $this->furthest) {
            $this->furthest = $position;
            $this->token = $this->buffer->current;
            $this->expected = [$expectedTokenId => $expectedTokenId];

            return;
        }

        if ($position === $this->furthest) {
            $this->expected[$expectedTokenId] = $expectedTokenId;
        }
    }

    public function finish(): Failure
    {
        return new Failure(
            token: $this->token,
            expected: \array_values($this->expected),
        );
    }
}
