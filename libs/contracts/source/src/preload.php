<?php

/**
 * The names of every symbol the "phplrt/source-contracts" package declares,
 * ordered so that a symbol is preceded by every symbol of this package its
 * declaration depends on.
 */

declare(strict_types=1);

return [
    Phplrt\Contracts\Source\Exception\SourceExceptionInterface::class,
    Phplrt\Contracts\Source\ReadableStreamInterface::class,
    Phplrt\Contracts\Source\ReadableInterface::class,
    Phplrt\Contracts\Source\FileInterface::class,
];
