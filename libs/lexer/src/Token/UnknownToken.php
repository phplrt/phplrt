<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

class UnknownToken extends Token
{
    /**
     * Name of the token that marks the unknown data.
     *
     * @var non-empty-string
     */
    public const DEFAULT_TOKEN_NAME = 'T_UNKNOWN';

    /**
     * @param string|int<0, max> $name
     * @param int<0, max> $offset
     */
    public function __construct(
        string $value,
        int $offset = 0,
        $name = self::DEFAULT_TOKEN_NAME
    ) {
        parent::__construct($name, $value, $offset);
    }
}
