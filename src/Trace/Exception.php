<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Trace;

use Phplrt\Position\Position;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\FileInterface;
use Phplrt\Source\ReadableInterface;

/**
 * Class Exception
 */
final class Exception
{
    /**
     * Exception constructor.
     */
    private function __construct()
    {
        // This is a static class
    }

    /**
     * @param \Throwable $e
     * @param int $offset
     * @param ReadableInterface $source
     * @return \Throwable
     * @throws NotAccessibleException
     * @throws \ReflectionException
     * @throws \RuntimeException
     */
    public static function patch(\Throwable $e, int $offset, ReadableInterface $source): \Throwable
    {
        if ($source instanceof FileInterface) {
            self::insert($e, 'line', Position::fromOffset($source, $offset)->getLine());
            self::insert($e, 'file', $source->getPathname());
        }

        return $e;
    }

    /**
     * @param \Throwable $ctx
     * @param string $property
     * @param mixed $value
     * @return void
     * @throws \ReflectionException
     */
    private static function insert(\Throwable $ctx, string $property, $value): void
    {
        if (\property_exists($ctx, $property)) {
            $reflection = new \ReflectionProperty($ctx, $property);

            $reflection->setAccessible(true);
            $reflection->setValue($ctx, $value);
        }
    }
}
