<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Bench;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\SourceMap\Codec\Base64Vlq;
use Phplrt\SourceMap\Codec\CodecInterface;

#[BeforeMethods('boot')]
#[Revs(3), Iterations(5), Warmup(2)]
class VlqBench
{
    private string $source1024;
    private string $source65536;
    private string $source1048576;

    /**
     * @var CodecInterface
     */
    private CodecInterface $vlq;

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->vlq = new Base64Vlq();

        $this->source1024 = \file_get_contents(__DIR__ . '/vlq.1024.txt');
        $this->source65536 = \file_get_contents(__DIR__ . '/vlq.65536.txt');
        $this->source1048576 = \file_get_contents(__DIR__ . '/vlq.1048576.txt');
    }

    public function bench1024(): void
    {
        foreach ($this->vlq->decode($this->source1024) as $_);
    }

    public function bench65536(): void
    {
        foreach ($this->vlq->decode($this->source65536) as $_);
    }

    public function bench1048576(): void
    {
        foreach ($this->vlq->decode($this->source1048576) as $_);
    }
}
