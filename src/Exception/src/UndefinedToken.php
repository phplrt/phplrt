<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Position\PositionInterface;

/**
 * @internal This class can be used for internal representation of exceptions
 */
final class UndefinedToken implements TokenInterface
{
    /**
     * @var PositionInterface
     */
    private PositionInterface $position;

    /**
     * @param PositionInterface $position
     */
    public function __construct(PositionInterface $position)
    {
        $this->position = $position;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return TokenInterface::END_OF_INPUT;
    }

    /**
     * {@inheritDoc}
     */
    public function getOffset(): int
    {
        return $this->position->getOffset();
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getBytes(): int
    {
        return 0;
    }
}
