<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @template-implements \IteratorAggregate<array-key, TokenInterface>
 */
interface CompositeTokenInterface extends TokenInterface, \IteratorAggregate, \Countable, \ArrayAccess
{
}
