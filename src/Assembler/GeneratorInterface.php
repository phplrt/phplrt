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
 * Interface GeneratorInterface
 */
interface GeneratorInterface
{
    /**
     * @param string ...$description
     * @return GeneratorInterface|$this
     */
    public function withDescription(string ...$description): self;

    /**
     * @param string $name
     * @param mixed $value
     * @return GeneratorInterface
     */
    public function withTag(string $name, $value): self;

    /**
     * @param string|null $namespace
     * @return GeneratorInterface|$this
     */
    public function withNamespace(?string $namespace): self;

    /**
     * @param bool $enabled
     * @return GeneratorInterface|$this
     */
    public function strict(bool $enabled = true): self;

    /**
     * @param string|null $directory
     * @param string|null $filename
     * @return string
     */
    public function save(string $directory = null, string $filename = null): string;

    /**
     * @return string
     */
    public function render(): string;

    /**
     * @return string
     */
    public function __toString(): string;
}
