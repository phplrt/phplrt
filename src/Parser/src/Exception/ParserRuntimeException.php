<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\RuntimeException;

abstract class ParserRuntimeException extends RuntimeException
{
    /**
     * @param string $message
     * @param ReadableInterface $src
     * @param TokenInterface|null $tok
     * @param \Throwable|null $prev
     */
    final public function __construct(
        string $message,
        ReadableInterface $src,
        ?TokenInterface $tok,
        \Throwable $prev = null
    ) {
        parent::__construct($message, 0, $prev);

        $this->setSource($src);
        $this->setToken($tok);
    }
}
