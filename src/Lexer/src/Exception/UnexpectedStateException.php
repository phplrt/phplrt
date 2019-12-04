<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Lexer\Token\Token;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class UnrecognizedStateException
 */
class UnexpectedStateException extends LexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_UNEXPECTED_STATE = 'Unrecognized token state #%s';

    /**
     * UnexpectedStateException constructor.
     *
     * @param int|string $state
     * @param TokenInterface $token
     * @param \Throwable|null $prev
     */
    public function __construct($state, TokenInterface $token = null, \Throwable $prev = null)
    {
        $token = $token ?? new Token('', '', 0);

        parent::__construct(\sprintf(static::ERROR_UNEXPECTED_STATE, $state), $token, $prev);
    }
}
