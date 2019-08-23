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
 * @mixin MutatesAttributesInterface
 */
trait MutableAttributesTrait
{
    use AttributesTrait;

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
}
