<?php

/**
 * The names of every symbol the "phplrt/parser-contracts" package declares,
 * ordered so that a symbol is preceded by every symbol of this package its
 * declaration depends on.
 */

declare(strict_types=1);

return [
    Phplrt\Contracts\Parser\ParserInterface::class,
    Phplrt\Contracts\Parser\Exception\ParserExceptionInterface::class,
    Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface::class,
];
