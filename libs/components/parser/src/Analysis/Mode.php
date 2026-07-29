<?php

declare(strict_types=1);

namespace Phplrt\Parser\Analysis;

/**
 * How much of the work an analysis does.
 */
enum Mode
{
    /**
     * Recognizes the source without building anything of it.
     *
     * The result tells how much of the source the grammar reads and what
     * stands in the way, and carries no value.
     */
    case SyntaxCheck;

    /**
     * Recognizes the source and builds the value the grammar describes.
     *
     * A source the grammar reads only in part is built into the value of that
     * part.
     */
    case Tolerant;
}
