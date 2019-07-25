<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Grammar\Delegate;

use Phplrt\Ast\Node;

/**
 * Class TokenDelegate
 */
class TokenDelegate extends Node
{
    /**
     * @var string
     */
    private $tokenName;

    /**
     * @var string
     */
    private $pattern;

    /**
     * TokenDelegate constructor.
     *
     * @param string $name
     * @param array $children
     * @param int $offset
     */
    public function __construct(string $name, array $children = [], int $offset = 0)
    {
        parent::__construct($name, $children, $offset);

        $value = $this->getChild(0)->getValue();

        [$this->tokenName, $this->pattern] = \preg_split('/\s+/', \substr($value, 7));
    }

    /**
     * @return bool
     */
    public function isKept(): bool
    {
        return $this->getChild(0)->getName() === 'T_TOKEN';
    }

    /**
     * @return string
     */
    public function getTokenName(): string
    {
        return $this->tokenName;
    }

    /**
     * @return string
     */
    public function getTokenPattern(): string
    {
        return $this->pattern;
    }
}
