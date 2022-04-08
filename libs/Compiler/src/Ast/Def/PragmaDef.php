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
 * Class PragmaDef
 * @internal Compiler's grammar AST node class
 */
class PragmaDef extends Definition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $value;

    /**
     * Pragma constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct(string $name, string $value)
    {
        $this->name  = $name;
        $this->value = $value;
    }
}
