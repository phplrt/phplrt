<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

return new ApplicationConfig(
    src: [__DIR__ . '/src'],
    suites: [
        new SuiteConfig(name: 'library', location: [__DIR__ . '/tests']),
    ],
);
