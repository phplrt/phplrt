<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\Internal\ContentReader;

use Phplrt\Source\Internal\ContentReaderInterface;

/**
 * @internal This is an internal library class, please do not use it in your code.
 * @psalm-internal Phplrt\Source
 */
final class ContentReader implements ContentReaderInterface
{
    /**
     * ContentReader constructor.
     *
     * @param string $content
     */
    public function __construct(
        private readonly string $content
    ) {
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->content;
    }
}
