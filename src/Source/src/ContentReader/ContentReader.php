<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source\ContentReader;

class ContentReader implements ContentReaderInterface
{
    /**
     * @var string
     */
    private string $content;

    /**
     * ContentReader constructor.
     *
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        return $this->content;
    }
}
