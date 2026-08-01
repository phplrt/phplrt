<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use Phplrt\Bench\Generated\PhplrtV4Parser;
use Phplrt\Compiler\Compiler;
use Phplrt\Parser\Parser;
use Phplrt\Source\File;
use Phplrt\Source\Source;

#[Revs(1)]
#[Iterations(5)]
#[RetryThreshold(5)]
#[BeforeMethods('prepare')]
final readonly class PhplrtV4Bench extends PhpDocTypeBenchCase
{
    protected const string TOOL = 'phplrt-v4';

    private Parser $parser;

    protected function boot(string $directory): void
    {
        if (isset($this->parser)) {
            return;
        }

        $compiledParser = self::CACHE_DIRECTORY . '/PhplrtV4Parser.php';

        if (!\is_file($compiledParser)) {
            new Compiler()
                ->load(new File(self::grammar('PhpDocType.pp3')))
                ->generate()
                    ->withNamespaceName('Phplrt\Bench\Generated')
                    ->withClassName('PhplrtV4Parser')
                    ->save($compiledParser);
        }

        require_once $compiledParser;

        $this->parser = new PhplrtV4Parser();
    }

    public function benchCommonType25b(): void
    {
        foreach ($this->types['common-type-25b.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }

    public function benchCommonType250b(): void
    {
        foreach ($this->types['common-type-250b.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }

    public function benchCommonType1k(): void
    {
        foreach ($this->types['common-type-1k.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }

    public function benchCommonType10k(): void
    {
        foreach ($this->types['common-type-10k.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }

    public function benchCommonType100k(): void
    {
        foreach ($this->types['common-type-100k.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }

    public function benchCommonType250k(): void
    {
        foreach ($this->types['common-type-250k.txt'] as $type) {
            $this->parser->parse(new Source($type));
        }
    }
}
