<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionInterface;

/**
 * @internal This class can be used for internal representation of exceptions
 */
final class UndefinedToken implements TokenInterface
{
    public function __construct(private PositionInterface $position) {}

    public function getName(): string
    {
        return TokenInterface::END_OF_INPUT;
    }

    public function getOffset(): int
    {
        return $this->position->getOffset();
    }

    public function getValue(): string
    {
        return '';
    }

    public function getBytes(): int
    {
        return 0;
    }
}
