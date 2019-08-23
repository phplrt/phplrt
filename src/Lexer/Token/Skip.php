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
 * Class Skip
 */
class Skip extends BaseToken
{
    /**
     * @var int
     */
    public const ID = self::TYPE_SKIP;

    /**
     * @var string
     */
    public const NAME = 'T_SKIP';

    /**
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $offset;

    /**
     * Unknown constructor.
     *
     * @param string $value
     * @param int $offset
     */
    public function __construct(string $value, int $offset = 0)
    {
        $this->value = $value;
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return static::ID;
    }

    /**
     * @return int
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
