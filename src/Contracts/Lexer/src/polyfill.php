<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Phplrt\Parser\Buffer\BufferInterface;

if (! \interface_exists(BufferInterface::class)) {
    /**
     * "Phplrt\Contracts\Lexer\BufferInterface" interface is deprecated
     * since version 2.3 and will be removed in 3.0.
     */
    \class_alias(
        \Phplrt\Contracts\Lexer\BufferInterface::class,
        BufferInterface::class
    );
}
