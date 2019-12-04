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
use Phplrt\Contracts\Lexer\Exception\LexerRuntimeExceptionInterface;

/**
 * Class LexerException
 */
class LexerRuntimeException extends LexerException implements LexerRuntimeExceptionInterface
{
    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * LexerRuntimeException constructor.
     *
     * @param string $message
     * @param TokenInterface|null $token
     * @param \Throwable|null $prev
     */
    public function __construct(string $message, TokenInterface $token = null, \Throwable $prev = null)
    {
        parent::__construct($message, 0, $prev);

        $this->token = $token ?? Token::empty();
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
