<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\MonorepoBuilder\ValueObject\Option;
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


return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::PACKAGE_ALIAS_FORMAT, '<major>.<minor>.x-dev');
    $parameters->set(Option::PACKAGE_DIRECTORIES, ['libs', 'libs/contracts', 'libs/meta']);

    $parameters->set(Option::DATA_TO_APPEND, [
        'autoload-dev' => [
            'psr-4' => [
                'Phplrt\\Bench\\' => 'tests/bench',
            ]
        ],
        'require-dev' => [
            'symplify/monorepo-builder' => '^10.1',
            'phpunit/phpunit' => '^9.5',
            'squizlabs/php_codesniffer' => '^3.6',
            'symfony/var-dumper' => '^5.4|^6.0',
            'vimeo/psalm' => '^4.22',
        ],
    ]);

    $services = $containerConfigurator->services();

    # Release Workers
    $services->set(SetCurrentMutualDependenciesReleaseWorker::class);
    $services->set(AddTagToChangelogReleaseWorker::class);
    $services->set(TagVersionReleaseWorker::class);
    $services->set(PushTagReleaseWorker::class);
    $services->set(SetNextMutualDependenciesReleaseWorker::class);
    $services->set(UpdateBranchAliasReleaseWorker::class);
    $services->set(PushNextDevReleaseWorker::class);
};
