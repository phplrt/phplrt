<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;

#[Revs(1)]
#[Iterations(5)]
#[RetryThreshold(5)]
#[BeforeMethods('prepare')]
final readonly class PsalmBench extends PhpDocTypeBenchCase
{
    protected const string TOOL = 'psalm';

    private const string CONFIG = <<<'XML'
        <?xml version="1.0"?>
        <psalm xmlns="https://getpsalm.org/schema/config" errorLevel="1" resolveFromConfigFile="true">
            <projectFiles><directory name="." /></projectFiles>
        </psalm>
        XML;

    private bool $booted;

    protected function boot(string $directory): void
    {
        if (isset($this->booted)) {
            return;
        }

        $config = Config::loadFromXML($directory, self::CONFIG);
        $config->setIncludeCollector(new IncludeCollector());
        $config->cache_directory = null;

        new ProjectAnalyzer($config, new Providers(new FakeFileProvider()));

        $this->booted = true;
    }

    public function benchCommonType25b(): void
    {
        foreach ($this->types['common-type-25b.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }

    public function benchCommonType250b(): void
    {
        foreach ($this->types['common-type-250b.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }

    public function benchCommonType1k(): void
    {
        foreach ($this->types['common-type-1k.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }

    public function benchCommonType10k(): void
    {
        foreach ($this->types['common-type-10k.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }

    public function benchCommonType100k(): void
    {
        foreach ($this->types['common-type-100k.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }

    public function benchCommonType250k(): void
    {
        foreach ($this->types['common-type-250k.txt'] as $type) {
            TypeParser::parseTokens(TypeTokenizer::tokenize($type));
        }
    }
}
