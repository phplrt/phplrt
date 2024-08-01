<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Token;

class Token extends BaseToken
{
    /**
     * @var int<0, max>
     */
    private static int $anonymousId = 0;

    /**
     * @var array-key
     */
    private string|int $name;

    private string $value;

    /**
     * @var int<0, max>
     */
    private int $offset;

    /**
     * @param array-key $name
     * @param int<0, max> $offset
     */
    public function __construct(string|int $name, string $value, int $offset = 0)
    {
        if ($name === '') {
            $name = self::$anonymousId++;
        }

        $this->name = $name;
        $this->value = $value;
        $this->offset = $offset;
    }

    public static function empty(): UnknownToken
    {
        return new UnknownToken('');
    }

    public function getName(): string
    {
        if (\is_string($this->name)) {
            return $this->name;
        }

        return '#' . $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }
}
