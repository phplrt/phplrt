<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Interface for parser exceptions thrown in case of an error during code
 * execution.
 */
interface ParserRuntimeExceptionInterface extends ParserExceptionInterface
{
    /**
     * Returns the token on which the error occurred.
     *
     * @return TokenInterface
     */
    public function getToken(): TokenInterface;

    /**
     * Returns the source code object in which the error occurred.
     *
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;
}
