<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine\Result;

use Phplrt\Parser\Internal\Engine\Failure;

/**
 * The grammar has read nothing of the source.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @readonly
 */
final class FailureEngineResult extends EngineResult
{
    public function __construct(
        public readonly Failure $failure,
    ) {
        parent::__construct();
    }
}
