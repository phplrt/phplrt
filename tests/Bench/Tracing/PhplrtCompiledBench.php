<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Compiler\Compiler;
use Phplrt\Parser\Parser;
use Phplrt\Source\File;
use Phplrt\Source\Source;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(2)]
#[RetryThreshold(0.2)]
#[BeforeMethods('prepare')]
final readonly class PhplrtCompiledBench extends PhplrtBench
{
    private Parser $parser;

    public function prepare(): void
    {
        if (!\is_readable(__DIR__ . '/generated.php')) {
            new Compiler()
                ->load(new File(__DIR__ . '/grammar.pp2'))
                ->generate()
                ->withClassImport('TypeLang\Parser\Exception')
                ->withClassImport('TypeLang\Type')
                ->save(__DIR__ . '/generated.php');
        }

        $this->parser = require __DIR__ . '/generated.php';
    }

    public function benchParsing(): void
    {
        $this->parser->parse(new Source(self::SAMPLE));
    }
}
