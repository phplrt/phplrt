<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

class UnexpectedTokenException extends UnrecognizedTokenException
{
    /**
     * @var string
     */
    public const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unexpected %s';

    /**
     * @param ReadableInterface $src
     * @param TokenInterface $tok
     * @param \Throwable|null $prev
     * @return static
     */
    public static function fromToken(ReadableInterface $src, TokenInterface $tok, \Throwable $prev = null): self
    {
        $message = \sprintf(self::ERROR_UNRECOGNIZED_TOKEN, self::getTokenValue($tok));

        return new static($message, $src, $tok, $prev);
    }
}
