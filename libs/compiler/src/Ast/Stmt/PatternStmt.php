<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Stmt;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PatternStmt extends Statement
{
    /**
     * @var array<non-empty-string, int<0, max>>
     */
    private static array $identifiers = [];

    /**
     * @var non-empty-string
     */
    public string $pattern;

    /**
     * @var int<0, max>
     */
    public int $name;

    /**
     * @param non-empty-string $pattern
     */
    public function __construct(string $pattern)
    {
        assert($pattern !== '', 'Pattern must not be empty');

        /** @psalm-suppress PropertyTypeCoercion: This replacement cannot make a non-empty string empty */
        $this->pattern = \str_replace('\"', '"', $pattern);

        $this->name = $this->getId();
    }

    /**
     * @return int<0, max>
     */
    private function getId(): int
    {
        return self::$identifiers[$this->pattern] ??= \count(self::$identifiers);
    }
}
