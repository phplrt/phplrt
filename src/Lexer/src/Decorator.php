<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;

abstract class Decorator implements LexerInterface
{
    /**
     * @var LexerInterface
     */
    private LexerInterface $lexer;

    /**
     * Decorator constructor.
     */
    public function __construct()
    {
        $this->lexer = $this->boot();
    }

    /**
     * @return LexerInterface
     */
    abstract protected function boot(): LexerInterface;

    /**
     * {@inheritDoc}
     */
    public function lex($source): iterable
    {
        return $this->lexer->lex($source);
    }
}
