<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class LexerException
 */
class LexerRuntimeException extends LexerException implements RuntimeExceptionInterface
{
    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * LexerRuntimeException constructor.
     *
     * @param string $message
     * @param TokenInterface $token
     * @param \Throwable|null $prev
     */
    public function __construct(string $message, TokenInterface $token, \Throwable $prev = null)
    {
        parent::__construct($message, 0, $prev);
        $this->token = $token;
    }

    /**
     * {@inheritDoc}
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
