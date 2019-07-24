<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver\Resolver;

use Phplrt\Assembler\Context\ContextInterface;
use PhpParser\Node\Name;

/**
 * Class Resolver
 */
abstract class Resolver implements ResolverInterface
{
    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * Resolver constructor.
     *
     * @param ContextInterface $context
     */
    public function __construct(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * @param int $type
     * @param Name $name
     * @param \Closure $filter
     * @return string|null
     */
    protected function lookup(int $type, Name $name, \Closure $filter): ?string
    {
        if ($fqn = $this->context->uses()->lookup($type, $name)) {
            return $fqn;
        }

        if (($fqn = $this->fqn($name)) && $filter($fqn)) {
            return $fqn;
        }

        if ($filter($name->toString())) {
            return $name->toString();
        }

        return null;
    }

    /**
     * @param Name $name
     * @return string|null
     */
    protected function fqn(Name $name): ?string
    {
        $namespace = $this->context->namespace();

        if ($namespace === null) {
            return null;
        }

        return \implode('\\', \array_merge($namespace->parts, $name->parts));
    }
}
