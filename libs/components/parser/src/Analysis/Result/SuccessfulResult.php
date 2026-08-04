<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis\Result;

/**
 * The grammar has read the source in full.
 *
 * @template-covariant TValue of mixed = null
 *
 * @template-extends Result<TValue>
 */
readonly class SuccessfulResult extends Result {}
