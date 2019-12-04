<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Interface GrammarInterface
 */
interface GrammarInterface extends ParserInterface
{
    /**
     * @param ReadableInterface $source
     * @return iterable
     */
    public function parse($source): iterable;
}
