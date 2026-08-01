<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use Hoa\Compiler\Llk\Llk;
use Hoa\Compiler\Llk\Parser;
use Hoa\File\Read;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;

#[Revs(1)]
#[Iterations(5)]
#[RetryThreshold(5)]
#[BeforeMethods('prepare')]
final readonly class HoaBench extends PhpDocTypeBenchCase
{
    protected const string TOOL = 'hoa';

    private Parser $parser;

    protected function boot(string $directory): void
    {
        if (isset($this->parser)) {
            return;
        }

        \error_reporting(\E_ERROR | \E_PARSE);
        \ini_set('display_errors', '0');

        $this->parser = Llk::load(new Read(self::grammar('PhpDocType.pp')));
    }

    public function benchCommonType25b(): void
    {
        foreach ($this->types['common-type-25b.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType250b(): void
    {
        foreach ($this->types['common-type-250b.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType1k(): void
    {
        foreach ($this->types['common-type-1k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType10k(): void
    {
        foreach ($this->types['common-type-10k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType100k(): void
    {
        foreach ($this->types['common-type-100k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType250k(): void
    {
        foreach ($this->types['common-type-250k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }
}
