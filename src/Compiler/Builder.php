<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Builder\LexerBuilder;

/**
 * Class Builder
 */
class Builder
{
    private $lexer;

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        $this->lexer = new LexerBuilder();
    }

    /**
     * @param \Closure|null $then
     * @return LexerBuilder
     */
    public function lexer(\Closure $then = null): LexerBuilder
    {
        if ($then) {
            $then($this->lexer);
        }

        return $this->lexer;
    }
}
