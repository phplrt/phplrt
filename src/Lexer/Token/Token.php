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
    private $type;

    /**
     * @var int
     */
    private $offset;

    /**
     * @var string
     */
    private $value;

    /**
     * BaseToken constructor.
     *
     * @param int $type
     * @param string $value
     * @param int $offset
     */
    public function __construct(int $type, string $value, int $offset)
    {
        $this->type = $type;
        $this->offset = $offset;
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
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
