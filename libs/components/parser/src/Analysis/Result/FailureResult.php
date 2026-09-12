<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * The grammar has read nothing of the source.
 *
 * @template-extends Result<never>
 *
 * @property-read RuntimeExceptionInterface $error What the analysis has to say about the source.
 *
 * @readonly
 */
final class FailureResult extends Result
{
    public function __construct(
        /**
         * The token the grammar has stopped on.
         */
        public readonly TokenInterface $token,
        /**
         * The error, or the means of building it until it is asked about.
         *
         * Saying what has stood in the way costs an exception object along with
         * the message it carries, and a caller that only asks how far the grammar
         * has got never asks about it at all.
         *
         * @var RuntimeExceptionInterface|(\Closure(): RuntimeExceptionInterface)
         *
         * @phpstan-readonly-allow-private-mutation
         */
        private RuntimeExceptionInterface|\Closure $error,
    ) {
        parent::__construct();
    }

    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'error':
                if ($this->error instanceof \Closure) {
                    return $this->error = ($this->error)();
                }

                return $this->error;

            default:
                throw new \Error(\sprintf('Undefined property %s::$%s', self::class, $property));
        }
    }

    public function __isset(string $property): bool
    {
        return $property === 'error';
    }
}
