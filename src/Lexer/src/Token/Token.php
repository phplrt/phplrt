<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Token;

/**
 * Class Token
 */
class Token extends BaseToken
{
    /**
     * @var int
     */
    private $offset;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $name;

    /**
     * BaseToken constructor.
     *
     * @param string $name
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $name, string $value, int $offset)
    {
        $this->name   = $name;
        $this->value  = $value;
        $this->offset = $offset;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }
}
