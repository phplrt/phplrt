<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Io\Readable;

/**
 * Interface LexerInterface
 */
interface LexerInterface
{
    /**
     * Compiling the current state of the lexer and returning
     * stream tokens from the source file.
     *
     * @param Readable $input
     * @return \Traversable|TokenInterface[]
     */
    public function lex(Readable $input): \Traversable;
}
