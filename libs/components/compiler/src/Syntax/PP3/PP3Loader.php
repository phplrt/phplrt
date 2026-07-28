<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP3;

use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Contracts\Parser\ParserInterface;

/**
 * Reads a PP3 grammar file into the lexer and the parser it describes.
 *
 * The format repeats PP2 up to the parser reading it, so everything the two
 * have in common is written down once, in the loader of the older format.
 */
final class PP3Loader extends PP2Loader
{
    protected function createParser(): ParserInterface
    {
        return new PP3Parser();
    }
}
