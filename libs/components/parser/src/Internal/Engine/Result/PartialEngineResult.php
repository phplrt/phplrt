<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Parser\Internal\Engine\Failure;

/**
 * The grammar has read the beginning of the source and stopped inside it.
 *
 * What has been read is built the way a whole source is, and what stood in
 * the way of the rest is described by the failure.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @readonly
 */
final class PartialEngineResult extends EngineResult
{
    public function __construct(
        mixed $value,
        /**
         * The token the reading has stopped at.
         */
        public readonly TokenInterface $stoppedAt,
        public readonly Failure $failure,
    ) {
        parent::__construct($value);
    }
}
