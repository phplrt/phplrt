<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/libs/contracts/*/src',
        __DIR__ . '/libs/*/src'
    ]);

    $config->sets([
        LevelSetList::UP_TO_PHP_74,
        SetList::TYPE_DECLARATION,
    ]);

    $config->skip([
        ClosureToArrowFunctionRector::class,
    ]);
};
