<?php
/**
 * This file is part of Assembler package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency;

use PhpParser\Node\Name;

/**
 * Interface DependencyInterface
 */
interface DependencyInterface
{
    /**
     * @return string
     */
    public function getAlias(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return array
     */
    public function getNamespace(): array;

    /**
     * @return string
     */
    public function getShortName(): string;

    /**
     * @return iterable|Name[]
     */
    public function getDependencies(): iterable;
}
