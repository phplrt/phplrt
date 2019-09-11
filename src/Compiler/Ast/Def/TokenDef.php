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
 * Class TokenDef
 * @internal Compiler's grammar AST node class
 */
class TokenDef extends Definition
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
     * @var bool
     */
    public $keep = true;

    /**
     * Pragma constructor.
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $name, string $value, int $offset)
    {
        $this->name = $name;
        $this->value = $value;

        parent::__construct($offset);
    }
}
