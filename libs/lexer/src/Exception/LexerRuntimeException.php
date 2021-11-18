<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;

abstract class LexerRuntimeException extends LexerException implements RuntimeExceptionInterface
{
    /**
     * @param string $message
     * @param ReadableInterface $source
     * @param TokenInterface $token
     * @param \Throwable|null $previous
     */
    final public function __construct(
        string $message,
        protected ReadableInterface $source,
        protected TokenInterface $token,
        \Throwable $previous = null
    ) {
        parent::__construct($message, (int)($previous?->getCode() ?? 0), $previous);

        if ($this->source instanceof FileInterface) {
            $position = Position::fromOffset($this->source, $this->token->getOffset());

            $this->file = $this->source->getPathname();
            $this->line = $position->getLine();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }

    /**
     * {@inheritDoc}
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }
}
