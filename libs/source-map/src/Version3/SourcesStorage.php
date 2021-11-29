<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\SourceMap\Version3;

use Phplrt\Contracts\Source\ReadableInterface;

/**
 * @template-extends \ArrayObject<positive-int|0, ReadableInterface>
 */
final class SourcesStorage extends \ArrayObject
{
    /**
     * @param ReadableInterface $source
     * @param iterable<ReadableInterface> $sources
     */
    public function __construct(
        private readonly ReadableInterface $source,
        iterable $sources = []
    ) {
        parent::__construct($sources);
    }

    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface
    {
        return $this->source;
    }
}
