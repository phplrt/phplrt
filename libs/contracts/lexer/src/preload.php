<?php

/**
 * The names of every symbol the "phplrt/lexer-contracts" package declares,
 * ordered so that a symbol is preceded by every symbol of this package its
 * declaration depends on.
 */

declare(strict_types=1);

return [
    Phplrt\Contracts\Lexer\ChannelInterface::class,
    Phplrt\Contracts\Lexer\TokenInterface::class,
    Phplrt\Contracts\Lexer\LexerInterface::class,
    Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface::class,
    Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface::class,
    Phplrt\Contracts\Lexer\Channel::class,
    Phplrt\Contracts\Lexer\UserDefinedChannel::class,
];
