<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing\Result;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final class FailureTracingResult extends TracingResult
{
    /**
     * @param array<int<0, max>, int|TokenInterface> $entries
     * @param int<0, max> $length
     */
    public function __construct(
        /**
         * The token the reading has stopped at, which is where everything the
         * grammar did read ends.
         */
        public TokenInterface $stoppedAt,
        /**
         * The token the reading has broken on, if anywhere in particular.
         */
        public ?TokenInterface $token = null,
        /**
         * The identifiers of the tokens that could have been read instead.
         *
         * @var list<int>
         */
        public array $expected = [],
        /**
         * The identifier of the rule describing the failure by a message of
         * its own, or {@see null} in case the failure is described by the
         * tokens that could have been read instead.
         */
        public ?int $labelled = null,
        array $entries = [],
        int $length = 0,
    ) {
        parent::__construct($entries, $length);
    }
}
