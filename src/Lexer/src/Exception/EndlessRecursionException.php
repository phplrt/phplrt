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
 * Class EndlessRecursionException
 */
class EndlessRecursionException extends LexerRuntimeException
{
    /**
     * @var string
     */
    private const ERROR_ENDLESS_TRANSITIONS = 'An unsolvable infinite lexer state transitions was found at %s';

    /**
     * EndlessRecursionException constructor.
     *
     * @param $state
     * @param TokenInterface|null $token
     * @param \Throwable|null $prev
     */
    public function __construct($state, TokenInterface $token = null, \Throwable $prev = null)
    {
        $token = $token ?? Token::empty();

        parent::__construct(\sprintf(static::ERROR_ENDLESS_TRANSITIONS, $token ?? $state), $token, $prev);
    }
}
