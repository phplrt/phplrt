<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * A production that recognizes a sequence of values instead of a single one.
 *
 * The value of such a rule is the list of the values of everything it has
 * recognized, while any other rule passes a single value through.
 *
 * ```abnf
 * rule = x y z ; << concat of "x y z" provides an array of children rules
 *              ; this is instance of SequenceInterface
 *
 * rule = x+    ; << repetition [1...max] of "x" also provides an array
 *              ; this is instance of SequenceInterface
 *
 * rule = x     ; << single rule (reference) to "x" provides only 1 rule
 *              ; this is NOT instance of SequenceInterface
 * ```
 */
interface SequenceInterface extends ProductionInterface {}
