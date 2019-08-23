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
 * Class EndOfInput
 */
class EndOfInput extends BaseToken
{
    /**
     * @var int
     */
    public const ID = self::TYPE_END_OF_INPUT;

    /**
     * @var string
     */
    public const NAME = 'T_EOI';

    /**
     * @var string
     */
    private const EOI_VALUE = "\0";

    /**
     * @var int
     */
    private $offset;

    /**
     * EndOfInput constructor.
     *
     * @param int $offset
     */
    public function __construct(int $offset = 0)
    {
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
        return self::EOI_VALUE;
    }

    /**
     * @return string|null
     */
    public function getState(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return 'end of input';
    }
}
