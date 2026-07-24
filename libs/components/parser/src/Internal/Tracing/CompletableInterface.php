<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Parser\Internal\Tracing\Result\Result;

/**
 * @template-covariant TResult of Result = Result
 */
interface CompletableInterface
{
    /**
     * @return TResult
     */
    public function finish(): Result;
}
