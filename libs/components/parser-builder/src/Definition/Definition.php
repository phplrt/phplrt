<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Definition;

use Phplrt\Contracts\Source\ReadableInterface;

abstract class Definition implements \Stringable
{
    /**
     * The place of the source code this definition has been written in, in
     * case it has been written at all rather than built by hand.
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?SourceReference $context = null;

    /**
     * What has been written about the definition in the source code, without
     * the characters the comment is wrapped into, or {@see null} in case of
     * nothing has been written about it.
     *
     * @var non-empty-string|null
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?string $comment = null;

    /**
     * @param int<0, max> $offset
     * @param int<0, max> $length
     * @return $this
     */
    public function setSource(ReadableInterface $source, int $offset, int $length = 0): self
    {
        $this->context = new SourceReference($source, $offset, $length);

        return $this;
    }

    /**
     * Updates what has been written about the definition and returns itself as
     * the fluent interface.
     *
     * @api
     *
     * @param non-empty-string|null $comment
     * @return $this
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
