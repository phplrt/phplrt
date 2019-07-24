<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Generator;

/**
 * Interface RendererInterface
 */
interface RendererInterface
{
    /**
     * @param iterable $ast
     * @param bool $raw
     * @return string
     */
    public function render(iterable $ast, bool $raw = true): string;
}
