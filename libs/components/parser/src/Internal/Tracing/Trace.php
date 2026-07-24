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
     * @var list<int>
     */
    private array $type = [];

    /**
     * @var list<int|TokenInterface>
     */
    private array $node = [];

    /**
     * @var int<0, max>
     */
    private int $length = 0;

    public function enter(int $rule): void
    {
        $this->type[$this->length] = Success::ENTER;
        $this->node[$this->length] = $rule;
        ++$this->length;
    }

    public function leave(int $rule): void
    {
        $this->type[$this->length] = Success::LEAVE;
        $this->node[$this->length] = $rule;
        ++$this->length;
    }

    public function token(TokenInterface $token): void
    {
        $this->type[$this->length] = Success::TOKEN;
        $this->node[$this->length] = $token;
        ++$this->length;
    }

    public function mark(): int
    {
        return $this->length;
    }

    public function rewind(int $mark): void
    {
        $this->length = $mark;
    }

    public function finish(): Success
    {
        return new Success(
            type: \array_slice($this->type, 0, $this->length),
            node: \array_slice($this->node, 0, $this->length),
        );
    }
}
