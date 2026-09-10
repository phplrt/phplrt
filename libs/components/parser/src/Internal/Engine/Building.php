<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Engine;

/**
 * What of the reading is worth building into a value.
 *
 * Recognizing a source and building what it describes are told apart so
 * that nothing is built for a reading nobody is going to look at.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
enum Building
{
    /**
     * Nothing is built: the source is only recognized.
     */
    case Nothing;

    /**
     * Only a source read to its end is built. The beginning of a source the
     * grammar has stopped inside is left as it is.
     */
    case Whole;

    /**
     * Whatever the grammar has read is built, the beginning of a source alone
     * included.
     */
    case Anything;
}
