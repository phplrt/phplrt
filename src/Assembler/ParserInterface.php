<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler;

/**
 * Interface ParserInterface
 */
interface ParserInterface
{
    /**
     * @param string $source
     * @param array $visitors
     * @return iterable
     */
    public function parse(string $source, array $visitors = []): iterable;

    /**
     * @param iterable $ast
     * @param array $visitors
     * @return iterable
     */
    public function modify(iterable $ast, array $visitors): iterable;
}
