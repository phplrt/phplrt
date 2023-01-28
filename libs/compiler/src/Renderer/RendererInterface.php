<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Renderer;

interface RendererInterface
{
    /**
     * @param int<0, max> $depth
     * @return string
     */
    public function prefix(int $depth): string;

    /**
     * @param mixed $data
     * @param int<0, max> $depth
     * @param bool $multiline
     * @return string
     */
    public function fromPhp($data, int $depth = 0, bool $multiline = true): string;

    /**
     * @param mixed $data
     * @param int<0, max> $depth
     * @param bool $multiline
     * @return string
     */
    public function fromString($data, int $depth = 0, bool $multiline = true): string;
}
