<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast\Dumper;

/**
 * Trait RenderableTrait
 */
trait RenderableTrait
{
    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    public function __toString(): string
    {
        try {
            return $this->dump();
        } catch (\Throwable $e) {
            return $this->getName() . ': ' . $e->getMessage();
        }
    }

    /**
     * @param NodeDumperInterface|string $dumper
     * @return string
     */
    public function dump(string $dumper = XmlDumper::class): string
    {
        /** @var string|NodeDumperInterface $dumper */
        $dumper = new $dumper($this);

        return $dumper->toString();
    }
}
