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

return static function (MBConfig $config): void {
    $config->packageAliasFormat('<major>.<minor>.x-dev');

    $config->packageDirectories([
        __DIR__ . '/libs',
        __DIR__ . '/libs/contracts',
        __DIR__ . '/libs/meta'
    ]);

    $config->dataToAppend([
        'require-dev' => [
            'friendsofphp/php-cs-fixer' => '^3.49',
            'phpunit/phpunit' => '^9.6|^10.0',
            'rector/rector' => '^1.0',
            'symfony/var-dumper' => '^5.4|^6.0',
            'symplify/monorepo-builder' => '^11.2',
            'vimeo/psalm' => '^5.21'
        ],
    ]);

    $services = $config->services();

    # Release Workers
    $services->set(SetCurrentMutualDependenciesReleaseWorker::class);
    $services->set(AddTagToChangelogReleaseWorker::class);
    $services->set(TagVersionReleaseWorker::class);
    $services->set(PushTagReleaseWorker::class);
    $services->set(SetNextMutualDependenciesReleaseWorker::class);
    $services->set(UpdateBranchAliasReleaseWorker::class);
    $services->set(PushNextDevReleaseWorker::class);
};
