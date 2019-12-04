<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Lexer\Token\Renderer;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class UnrecognizedTokenException
 */
class UnrecognizedTokenException extends LexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized %s';

    /**
     * UnrecognizedTokenException constructor.
     *
     * @param TokenInterface $token
     * @param \Throwable|null $prev
     */
    public function __construct(TokenInterface $token, \Throwable $prev = null)
    {
        $message = \vsprintf(self::ERROR_UNRECOGNIZED_TOKEN, [
            (new Renderer())->render($token),
        ]);

        parent::__construct($message, $token, $prev);
    }
}
