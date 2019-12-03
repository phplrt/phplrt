<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Contracts\Lexer\LexerInterface;

/**
 * Interface DriverInterface
 */
interface DriverInterface extends LexerInterface
{
    /**
     * @var string
     */
    public const UNKNOWN_TOKEN_NAME = 'T_UNKNOWN';
}
