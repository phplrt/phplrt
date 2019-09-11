<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Position;

/**
 * Class Position
 */
class Position
{
    use FactoryTrait;

    /**
     * @var int
     */
    public const MIN_LINE = 1;

    /**
     * @var int
     */
    public const MIN_COLUMN = 1;

    /**
     * @var int
     */
    public const MIN_OFFSET = 0;

    /**
     * @var string
     */
    protected const LINE_DELIMITER = "\n";

    /**
     * @var int
     */
    private $offset;

    /**
     * @var int
     */
    private $line;

    /**
     * @var int
     */
    private $column;

    /**
     * Position constructor.
     *
     * @param int $offset
     * @param int $line
     * @param int $column
     */
    public function __construct(
        int $offset = self::MIN_OFFSET,
        int $line = self::MIN_LINE,
        int $column = self::MIN_COLUMN
    ) {
        $this->offset = $offset;
        $this->line   = \max($line, static::MIN_LINE);
        $this->column = \max($column, static::MIN_COLUMN);
    }

    /**
     * @inheritdoc
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @inheritdoc
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @inheritdoc
     */
    public function getColumn(): int
    {
        return $this->column;
    }

    /**
     * @param \Throwable $e
     * @param string $pathname
     * @return \Throwable
     * @throws \ReflectionException
     */
    public function inject(\Throwable $e, string $pathname): \Throwable
    {
        $this->insert($e, 'line', $this->getLine());
        $this->insert($e, 'file', $pathname);

        return $e;
    }

    /**
     * @param \Throwable $ctx
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws \ReflectionException
     */
    private function insert(\Throwable $ctx, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($ctx, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($ctx, $value);
    }
}
