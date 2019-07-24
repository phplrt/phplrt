<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver\Resolver;

use Phplrt\Assembler\Context\Aliases;
use PhpParser\Node\Name;

/**
 * Class Functions
 */
class Functions extends Resolver
{
    /**
     * @param string|null $fqn
     * @return bool
     * @throws \ReflectionException
     */
    private function isInternal(?string $fqn): bool
    {
        return \is_string($fqn) && ! (new \ReflectionFunction($fqn))->isUserDefined();
    }

    /**
     * @param Name $name
     * @param \Closure $export
     * @return Name|null
     * @throws \ReflectionException
     */
    public function resolve(Name $name, \Closure $export): ?Name
    {
        $fqn = $this->lookup(Aliases::TYPE_FUNCTION, $name, function (string $fqn): bool {
            return $this->match($fqn);
        });

        if ($this->isInternal($fqn)) {
            return $name;
        }

        return $fqn ? $export(new Name($fqn)) : null;
    }

    /**
     * @param string $fqn
     * @return bool
     */
    private function match(string $fqn): bool
    {
        return \function_exists($fqn);
    }
}
