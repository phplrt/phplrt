<?php

declare(strict_types=1);

namespace Phplrt\Bench;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

abstract readonly class BenchCase
{
    protected const string ROOT_DIRECTORY = __DIR__ . '/..';
    protected const string TOOLS_DIRECTORY = self::ROOT_DIRECTORY . '/tools';
    protected const string CACHE_DIRECTORY = self::ROOT_DIRECTORY . '/var';


    /**
     * @param non-empty-string $tool
     * @return non-empty-string
     */
    protected static function install(string $tool): string
    {
        $directory = self::TOOLS_DIRECTORY . '/' . $tool;
        $autoload = $directory . '/vendor/autoload.php';

        if (!\is_file($autoload)) {
            $composer = new ExecutableFinder()->find('composer')
                ?? throw new \RuntimeException('Could not find the "composer" executable');

            new Process([$composer, 'install', '--no-interaction', '--no-progress'], $directory)
                ->setTimeout(600.0)
                ->mustRun();
        }

        require_once $autoload;

        return $directory;
    }
}
