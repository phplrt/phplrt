<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\Config\MBConfig;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\AddTagToChangelogReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushNextDevReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\PushTagReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetCurrentMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\SetNextMutualDependenciesReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\TagVersionReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateBranchAliasReleaseWorker;
use Symplify\MonorepoBuilder\Release\ReleaseWorker\UpdateReplaceReleaseWorker;

/**
 * Fixes:
 * 1) Bug: https://github.com/symplify/monorepo-builder/issues/105
 * 2) Bug: https://github.com/symplify/monorepo-builder/issues/106
 * 3) Bug: https://github.com/symplify/monorepo-builder/issues/107
 */
const COMPOSER_JSON = __DIR__ . '/composer.json';

$before = \json_decode(\file_get_contents(COMPOSER_JSON), true);

\register_shutdown_function(function () use ($before): void {
    $after = \json_decode(\file_get_contents(COMPOSER_JSON), true);

    foreach ($after as $key => $value) {
        $before[$key] ??= $value;
    }

    \file_put_contents(COMPOSER_JSON, \json_encode($before, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) . "\n");
});


return static function (MBConfig $config): void {
    $config->packageAliasFormat('<major>.<minor>.x-dev');

    $config->packageDirectories([
        __DIR__ . '/libs/components',
        __DIR__ . '/libs/contracts',
        __DIR__ . '/libs/meta',
    ]);

    $config->workers([
        UpdateReplaceReleaseWorker::class,
        SetCurrentMutualDependenciesReleaseWorker::class,
        AddTagToChangelogReleaseWorker::class,
        TagVersionReleaseWorker::class,
        PushTagReleaseWorker::class,
        SetNextMutualDependenciesReleaseWorker::class,
        UpdateBranchAliasReleaseWorker::class,
        PushNextDevReleaseWorker::class,
    ]);
};
