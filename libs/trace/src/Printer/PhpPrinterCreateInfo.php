<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace\Printer;

final class PhpPrinterCreateInfo
{
    /**
     * @param string $eol End of line delimiter
     * @param string $delimiter Delimiter between file and invocation statement representation
     * @param bool $index Print trace item index
     * @param bool $columns Print trace item position column
     * @param bool $main Print terminal "{main}" statement
     */
    public function __construct(
        public readonly string $eol = \PHP_EOL,
        public readonly string $delimiter = ': ',
        public readonly bool $index = true,
        public readonly bool $columns = false,
        public readonly bool $main = true,
    ) {
    }
}
