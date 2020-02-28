<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Token;
use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class EndlessRecursionException
 */
class EndlessRecursionException extends UnexpectedStateException
{
    /**
     * @var string
     */
    private const ERROR_ENDLESS_TRANSITIONS = 'An unsolvable infinite lexer state transitions was found at %s';

    /**
     * {@inheritDoc}
     */
    public static function fromState($state, ReadableInterface $src, ?TokenInterface $tok, \Throwable $e = null): self
    {
        $message = \sprintf(self::ERROR_ENDLESS_TRANSITIONS, $state);

        return new static($message, $src, $tok, $e);
    }
}
