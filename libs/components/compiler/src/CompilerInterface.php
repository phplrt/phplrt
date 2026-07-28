<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;

interface CompilerInterface
{
    /**
     * Reads the given grammar along with every grammar it refers to.
     *
     * @throws CompilerRuntimeException in case of the grammar says something that
     *         cannot be expressed or refers to a grammar that cannot be found
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface in case of the grammar cannot be
     *         recognized
     * @throws SourceExceptionInterface in case of the grammar cannot be read
     */
    public function load(ReadableInterface $source): void;

    public function build(): CompilerResultInterface;
}
