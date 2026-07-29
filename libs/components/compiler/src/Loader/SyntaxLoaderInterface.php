<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Loader;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Reads a grammar file written in a single format into the lexer and the
 * parser it describes.
 *
 * A loader describes nothing but the format it reads: everything a grammar
 * says about itself is written into the given builders, and the only thing
 * left for the caller is the grammars this one is built of.
 */
interface SyntaxLoaderInterface
{
    /**
     * Reads the given grammar, adding the tokens it declares to the lexer and
     * the rules it declares to the parser.
     *
     * The references are given away while the grammar is being read rather
     * than after it: reading one of them the moment it is returned puts the
     * declarations of the grammar it points at exactly where the reference is
     * written.
     *
     * @return iterable<array-key, GrammarReference> the grammars the given one
     *         is built of, in the order they are referred to
     * @throws CompilerRuntimeException in case of the grammar says something that
     *         cannot be expressed
     * @throws RuntimeExceptionInterface in case of the grammar cannot be
     *         recognized
     * @throws SourceExceptionInterface in case of the grammar cannot be read
     */
    public function load(ReadableInterface $source, ParserBuilder $parser, LexerBuilder $lexer): iterable;
}
