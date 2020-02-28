<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Interface RuntimeExceptionInterface
 */
interface RuntimeExceptionInterface extends \Throwable
{
    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface;

    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface;
}
