<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency;

use PhpParser\Node\Name;

/**
 * Class Dependency
 */
abstract class Dependency implements DependencyInterface
{
    /**
     * @var Name
     */
    private $fqn;

    /**
     * @var string|null
     */
    private $alias;

    /**
     * Dependency constructor.
     *
     * @param string|Name $fqn
     */
    public function __construct($fqn)
    {
        $this->fqn = $this->resolveName($fqn);
    }

    /**
     * @param string|Name $fqn
     * @return Name
     */
    private function resolveName($fqn): Name
    {
        return \is_string($fqn) ? new Name($fqn) : $fqn;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias ?? $this->alias = $this->createAlias();
    }

    /**
     * @return string
     */
    private function createAlias(): string
    {
        return \str_replace('.', '', \uniqid($this->getShortName(), true));
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->fqn->toString();
    }

    /**
     * @return array
     */
    public function getNamespace(): array
    {
        return \array_slice($this->fqn->parts, 0, -1);
    }

    /**
     * @return string
     */
    public function getShortName(): string
    {
        return \array_slice($this->fqn->parts, -1)[0];
    }
}
