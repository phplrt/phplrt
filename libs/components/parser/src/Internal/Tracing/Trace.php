<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * Records the grammar rules applied during recognition, producing the parse tree.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser\Internal
 *
 * @template-implements CompletableInterface<Success>
 */
final class Trace implements CompletableInterface
{
    /**
     * @var array<int<0, max>, int>
     */
    private array $type = [];

    /**
     * @var array<int<0, max>, int|TokenInterface>
     */
    private array $node = [];

    /**
     * @var int<0, max>
     */
    private int $length = 0;

    public function enter(int $rule): void
    {
        $length = $this->length;
        $this->type[$length] = Success::TYPE_ENTER;
        $this->node[$length] = $rule;
        ++$this->length;
    }

    public function leave(int $rule): void
    {
        $length = $this->length;
        $this->type[$length] = Success::TYPE_LEAVE;
        $this->node[$length] = $rule;
        ++$this->length;
    }

    public function token(TokenInterface $token): void
    {
        $length = $this->length;
        $this->type[$length] = Success::TYPE_TOKEN;
        $this->node[$length] = $token;
        ++$this->length;
    }

    /**
     * @return int<0, max>
     */
    public function mark(): int
    {
        return $this->length;
    }

    /**
     * @param int<0, max> $mark
     */
    public function rewind(int $mark): void
    {
        $this->length = $mark;
    }

    public function finish(): Success
    {
        return new Success(
            types: $this->type,
            references: $this->node,
            length: $this->length,
        );
    }
}
