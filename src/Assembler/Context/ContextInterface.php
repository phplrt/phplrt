<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Context;

use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

/**
 * Interface ContextInterface
 */
interface ContextInterface
{
    /**
     * @return Name|null
     */
    public function namespace(): ?Name;

    /**
     * @param Name|Identifier $name
     * @return Name
     */
    public function fqn($name): Name;

    /**
     * @return Aliases
     */
    public function uses(): Aliases;
}
