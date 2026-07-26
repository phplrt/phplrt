<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(3)]
#[RetryThreshold(0.3)]
#[BeforeMethods('prepare')]
final readonly class PhplrtParsingBench extends PhplrtBench
{
    public function benchParsing(): void
    {
        $this->phplrt->parse(self::SAMPLE);
    }
}
