<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use Phplrt\Bench\BenchCase;

abstract readonly class PhpDocTypeBenchCase extends BenchCase
{
    /**
     * The directory under "tools/" holding the composer project of the tool.
     *
     * @var non-empty-string
     */
    protected const string TOOL = '';

    /**
     * @var list<non-empty-string>
     */
    private const array SAMPLES = [
        'common-type-25b.txt',
        'common-type-250b.txt',
        'common-type-1k.txt',
        'common-type-10k.txt',
        'common-type-100k.txt',
        'common-type-250k.txt',
    ];

    /**
     * The types of every sample, read and split before the measurement.
     *
     * @var array<non-empty-string, list<non-empty-string>>
     */
    protected array $types;

    public function prepare(): void
    {
        // Load samples
        if (!isset($this->types)) {
            $types = [];

            foreach (self::SAMPLES as $sample) {
                $sampleString = (string) @\file_get_contents(__DIR__ . '/Sample/' . $sample);
                $sampleArray = \explode("\n", $sampleString);

                $types[$sample] = $sampleArray;
            }

            $this->types = $types;
        }

        // Boot
        $this->boot(self::install(static::TOOL));
    }

    /**
     * @param non-empty-string $directory
     */
    abstract protected function boot(string $directory): void;

    /**
     * @return non-empty-string
     */
    protected static function grammar(string $name): string
    {
        return __DIR__ . '/Grammar/' . $name;
    }
}
