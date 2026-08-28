<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

$dirs = static fn(string $pattern): array => \glob(__DIR__ . '/' . $pattern, \GLOB_ONLYDIR) ?: [];

return new ApplicationConfig(
    src: $dirs('libs/*/*/src'),
    suites: [
        new SuiteConfig(name: 'components', location: $dirs('libs/components/*/tests')),
        new SuiteConfig(name: 'contracts', location: $dirs('libs/contracts/*/tests')),
    ],
);
