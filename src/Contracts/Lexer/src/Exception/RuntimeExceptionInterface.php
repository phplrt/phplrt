<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Throws when the error of the lexical analysis of the source code happens.
 *
 * @deprecated since version 2.1 and will be removed in 3.0. Use LexerRuntimeExceptionInterface instead
 */
interface RuntimeExceptionInterface extends LexerRuntimeExceptionInterface
{
    /**
     * Returns a token object during which processing errors occurred.
     *
     * @return TokenInterface
     */
    public function getToken(): TokenInterface;
}
