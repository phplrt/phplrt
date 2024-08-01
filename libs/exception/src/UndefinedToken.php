<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Lexer\Token\EndOfInput;

/**
 * @internal This class can be used for internal representation of exceptions
 */
final class UndefinedToken implements TokenInterface
{
    public function __construct(
        private readonly PositionInterface $position,
    ) {}

    public function getName(): string
    {
        return EndOfInput::DEFAULT_TOKEN_NAME;
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
