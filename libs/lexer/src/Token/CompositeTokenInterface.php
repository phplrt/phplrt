<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @template-extends \IteratorAggregate<int, TokenInterface>
 * @template-extends \ArrayAccess<int, TokenInterface>
 */
interface CompositeTokenInterface extends TokenInterface, \IteratorAggregate, \Countable, \ArrayAccess
{
}
