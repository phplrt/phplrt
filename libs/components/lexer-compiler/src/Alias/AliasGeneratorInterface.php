<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Alias;

interface AliasGeneratorInterface
{
    /**
     * @param int<0, max> $id
     * @return non-empty-string
     */
    public function getAliasById(int $id): string;
}
