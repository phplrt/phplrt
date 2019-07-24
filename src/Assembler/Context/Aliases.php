<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Context;

use PhpParser\Node\Name;

/**
 * Class Aliases
 */
class Aliases
{
    /**
     * @var int
     */
    public const TYPE_CLASS = 0x00;

    /**
     * @var int
     */
    public const TYPE_FUNCTION = 0x01;

    /**
     * @var int
     */
    public const TYPE_CONST = 0x02;

    /**
     * @var array|Name[]
     */
    private $aliases = [];

    /**
     * @param Name $class
     * @param string|null $alias
     * @param int $type
     * @return void
     */
    public function register(Name $class, string $alias = null, int $type = self::TYPE_CLASS): void
    {
        if (! isset($this->aliases[$type])) {
            $this->aliases[$type] = [];
        }

        $this->aliases[$type][$alias ?? $class->getLast()] = $class;
    }

    /**
     * @param Name $name
     * @return string|null
     */
    public function lookupClass(Name $name): ?string
    {
        return $this->lookup(static::TYPE_CLASS, $name);
    }

    /**
     * @param int $type
     * @param Name $name
     * @return string|null
     */
    public function lookup(int $type, Name $name): ?string
    {
        if (! isset($this->aliases[$type][$name->getFirst()])) {
            return null;
        }

        /** @var Name $definition */
        $definition = $this->aliases[$type][$name->getFirst()];

        $suffix = $name->parts;
        \array_shift($suffix);

        $fqn = \array_merge($definition->parts, $suffix);

        return \implode('\\', $fqn);
    }

    /**
     * @param Name $name
     * @return string|null
     */
    public function lookupFunction(Name $name): ?string
    {
        return $this->lookup(static::TYPE_FUNCTION, $name);
    }

    /**
     * @param Name $name
     * @return string|null
     */
    public function lookupConst(Name $name): ?string
    {
        return $this->lookup(static::TYPE_CONST, $name);
    }
}
