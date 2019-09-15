<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Token;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Class Nested
 */
class Nested extends Composite
{
    /**
     * BaseToken constructor.
     *
     * @param string $name
     * @param string $value
     * @param array|TokenInterface[] $children
     */
    public function __construct(string $name, string $value, array $children)
    {
        $first = new Token($name, $value, \reset($children) ? $children[0]->getOffset() : 0);

        parent::__construct(\array_merge([$first], $children));
    }
}
