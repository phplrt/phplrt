<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

use Phplrt\Buffer\BufferInterface;

/**
 * Interface denoting a production (that is a non-terminal) rule.
 */
interface ProductionInterface extends RuleInterface
{
    /**
     * Returns a successful (non-null) result if the current buffer state
     * is correctly processed. Otherwise, if the rule does not match the
     * required one, it returns null.
     *
     * Second "Closure" argument returns the result of the execution
     * of the passed state.
     *
     * An example of "Optional" (like ebnf "Some?") rule implementation, where
     * ebnf "Some" defined as state 42
     *
     * <code>
     *  public function reduce(BufferInterface $buffer, \Closure $reduce)
     *  {
     *      // When result of state 42 return non-null result then we
     *      // return this result.
     *      if (($resultOfState = $reduce(42)) !== null) {
     *          return $resultOfState;
     *      }
     *
     *      // Otherwise return an empty array.
     *      // An "Optional" rule always returns a non-null result.
     *      return [];
     *  }
     * </code>
     *
     * @return mixed|iterable|null
     */
    public function reduce(BufferInterface $buffer, \Closure $reduce);
}
