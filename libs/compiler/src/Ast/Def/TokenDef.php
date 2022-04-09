<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal Compiler's grammar AST node class
 */
class TokenDef extends Definition
{
    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $value;

    /**
     * @var bool
     */
    public bool $keep;

    /**
     * @var string|null
     */
    public ?string $state = null;

    /**
     * @var string|null
     */
    public ?string $next = null;

    /**
     * @param string $name
     * @param string $value
     * @param bool $keep
     */
    public function __construct(string $name, string $value, bool $keep = true)
    {
        $this->name  = $name;
        $this->value = $value;
        $this->keep  = $keep;
    }
}
