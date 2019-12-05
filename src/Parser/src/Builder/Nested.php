<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Builder;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Class Nested
 */
class Nested implements BuilderInterface
{
    /**
     * @var array|BuilderInterface[]
     */
    protected $builders = [];

    /**
     * Nested constructor.
     *
     * @param array|BuilderInterface[] $builders
     */
    public function __construct(array $builders)
    {
        $this->builders = $builders;
    }

    /**
     * {@inheritDoc}
     */
    public function build(ReadableInterface $file, RuleInterface $rule, TokenInterface $token, $state, $children)
    {
        foreach ($this->builders as $builder) {
            if (($result = $builder->build($file, $rule, $token, $state, $children)) !== null) {
                return $result;
            }
        }

        return null;
    }
}
