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

/**
 * Monorepo Builder additional fields
 *
 * @see https://github.com/symplify/symplify/issues/2061
 */
\register_shutdown_function(static function () {
    $dest = \json_decode(\file_get_contents(__DIR__ . '/composer.json'), true);

    $result = [
        'name' => 'phplrt/phplrt',
        'type' => 'library',
        'description' => $dest['description'] ?? '',
        'homepage' => 'https://phplrt.org',
        'license' => 'MIT',
        'support' => [
            'issues' => 'https://github.com/phplrt/phplrt/issues',
            'source' => 'https://github.com/phplrt/phplrt',
        ],
        'authors' => [
            [
                'name' => 'Kirill Nesmeyanov',
                'email' => 'nesk@xakep.ru',
            ],
        ],
        'require' => $dest['require'] ?? [],
        'autoload' => $dest['autoload'] ?? [],
        'require-dev' => $dest['require-dev'] ?? [],
        'autoload-dev' => $dest['autoload-dev'] ?? [],
        'replace' => $dest['replace'] ?? [],
        'scripts' => $dest['scripts'] ?? [],
        'extra' => $dest['extra'] ?? [],
        'config' => $dest['config'] ?? [],
        'minimum-stability' => $dest['minimum-stability'] ?? 'dev',
        'prefer-stable' => $dest['prefer-stable'] ?? true,
    ];

    $json = \json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);

    \file_put_contents(__DIR__ . '/composer.json', $json . "\n");
});


return static function (MBConfig $config): void {
    $config->packageAliasFormat('<major>.<minor>.x-dev');
    $config->packageDirectories([
        __DIR__ . '/libs',
        __DIR__ . '/libs/contracts',
        __DIR__ . '/libs/meta'
    ]);

    $config->dataToAppend([
        'require-dev' => [
            'friendsofphp/php-cs-fixer' => '^3.17',
            'phpunit/phpunit' => '^9.5.20',
            'rector/rector' => '^0.17',
            'symfony/var-dumper' => '^5.4|^6.0',
            'symplify/monorepo-builder' => '^11.2',
            'vimeo/psalm' => '^5.12',
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
