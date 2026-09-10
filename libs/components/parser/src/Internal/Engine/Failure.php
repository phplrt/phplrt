<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * What the reading has broken on.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 *
 * @readonly
 */
final class Failure
{
    public function __construct(
        /**
         * The token the reading could not go past.
         */
        public readonly TokenInterface $token,
        /**
         * The identifiers of the tokens the grammar could have read instead,
         * in case of it says anything about that.
         *
         * @var list<int>
         */
        public readonly array $expected = [],
        /**
         * The identifier of the rule describing the failure by a message of
         * its own, or {@see null} in case of no such rule was being
         * recognized when the reading has broken.
         */
        public readonly ?int $labelled = null,
    ) {}
}
