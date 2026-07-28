<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP;

use Phplrt\Compiler\Exception\UnsupportedFormatException;
use Phplrt\Compiler\Loader\SyntaxLoaderInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Reports a grammar file written in the PP format.
 *
 * The format is the one phplrt has read up to its 3.x versions and does not
 * read anymore. Such a grammar is still recognized by its extension, so that
 * it is reported as what it is instead of being read as something else.
 */
final readonly class PPLoader implements SyntaxLoaderInterface
{
    /**
     * @var non-empty-string
     */
    private const string FORMAT = 'pp';

    public function load(ReadableInterface $source, ParserBuilder $parser, LexerBuilder $lexer): iterable
    {
        throw UnsupportedFormatException::becauseFormatIsNotSupported($source, self::FORMAT);
    }
}
