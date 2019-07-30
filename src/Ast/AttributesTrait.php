<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Ast;

use Phplrt\Contracts\Ast\MutatesAttributesInterface;

/**
 * Trait AttributesTrait
 *
 * @mixin MutatesAttributesInterface
 */
trait AttributesTrait
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * {@inheritDoc}
     */
    public function withAttribute(string $name, $value): MutatesAttributesInterface
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function withAttributes(array $attributes): MutatesAttributesInterface
    {
        $this->attributes = \array_merge($this->attributes, $attributes);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributes(array $attributes): MutatesAttributesInterface
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAttribute(string $name): bool
    {
        return isset($this->attributes[$name]) || \array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
