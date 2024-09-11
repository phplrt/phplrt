<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $config): void {
    $config->paths([
        __DIR__ . '/libs/contracts/*/src',
        __DIR__ . '/libs/*/src'
    ]);

    $config->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::TYPE_DECLARATION,
    ]);

    $config->skip([
        // PHP 8.1
        \Rector\Php81\Rector\ClassMethod\NewInInitializerRector::class,
    ]);
};
