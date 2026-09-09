<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Node;

/**
 * A top level element of a grammar file.
 *
 * @phpstan-sealed FragmentDeclaration|IncludeDeclaration|LexerDeclaration|PragmaDeclaration|RuleDeclaration|TokenDeclaration
 *
 * @readonly
 */
abstract class Declaration extends Node
{
    /**
     * The comment written about the declaration, as it is written, or
     * {@see null} in case of nothing has been written about it.
     *
     * A comment says something about the element it stands before rather than
     * about the file it is written in, so which of the two a comment is about
     * is decided by the format that has read it.
     *
     * @var non-empty-string|null
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public ?string $comment = null;

    /**
     * Updates the comment written about the declaration and returns itself as
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
