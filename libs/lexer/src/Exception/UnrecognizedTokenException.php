<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnrecognizedTokenException extends LexerLexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized token %s';

    /**
     * @param ReadableInterface $source
     * @param TokenInterface $token
     * @param \Throwable|null $prev
     * @return static
     */
    public static function fromToken(ReadableInterface $source, TokenInterface $token, \Throwable $prev = null): self
    {
        $message = \sprintf(self::ERROR_UNRECOGNIZED_TOKEN, $token);

        return new static($message, $source, $token, $prev);
    }
}
