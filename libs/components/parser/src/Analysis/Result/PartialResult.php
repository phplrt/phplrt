<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * The grammar has read a fragment of the source and stopped.
 *
 * The fragment is the longest one the grammar recognizes.
 *
 * The property described below SHOULD BE considered an actual class
 * requirement. Its absence in the code is due to the error being built at the
 * moment it is asked about rather than at the moment the reading ends.
 *
 * @template-covariant TValue of mixed = null
 *
 * @template-extends SuccessfulResult<TValue>
 *
 * @property-read RuntimeExceptionInterface $error What the analysis has to say about the source.
 *
 * @readonly
 */
final class PartialResult extends SuccessfulResult
{
    /**
     * The error, or the means of building it until it is asked about.
     *
     * Saying what has stood in the way costs an exception object along with
     * the message it carries, and a reading that stops before the end of a
     * source is a normal outcome for a caller that reads as much as the
     * grammar describes and does something else with the rest. Such a caller
     * never asks about the error at all.
     *
     * @var RuntimeExceptionInterface|(\Closure(): RuntimeExceptionInterface)
     *
     * @phpstan-readonly-allow-private-mutation
     */
    private RuntimeExceptionInterface|\Closure $reported;

    /**
     * @param TValue $value
     * @param RuntimeExceptionInterface|(\Closure(): RuntimeExceptionInterface) $error
     */
    public function __construct(
        mixed $value,
        /**
         * The first token the grammar says nothing about, which is where the
         * fragment ends.
         */
        public readonly TokenInterface $token,
        RuntimeExceptionInterface|\Closure $error,
    ) {
        $this->reported = $error;

        parent::__construct($value);
    }

    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'error':
                if ($this->reported instanceof \Closure) {
                    $this->reported = ($this->reported)();
                }

                return $this->reported;

            default:
                throw new \Error(\sprintf('Undefined property %s::$%s', static::class, $property));
        }
    }
}
