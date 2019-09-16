<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * Class ParserRuntimeException
 */
class ParserRuntimeException extends ParserException implements RuntimeExceptionInterface
{
    /**
     * @var string
     */
    public const ERROR_UNEXPECTED_TOKEN = 'Syntax error, unexpected %s';

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
        $this->token = $token;

        parent::__construct($message, 0, $prev);
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
