<?php

declare(strict_types=1);

namespace Phplrt\Position;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceExceptionInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\SourceFactory;

trait PositionFactoryTrait
{
    private static ?PositionFactoryInterface $positionFactory = null;

    private static ?SourceFactoryInterface $sourceFactory = null;

    public static function setPositionFactory(PositionFactoryInterface $factory): void
    {
        self::$positionFactory = $factory;
    }

    public static function getPositionFactory(): PositionFactoryInterface
    {
        return self::$positionFactory ??= new PositionFactory();
    }

    public static function setSourceFactory(SourceFactoryInterface $factory): void
    {
        self::$sourceFactory = $factory;
    }

    public static function getSourceFactory(): SourceFactoryInterface
    {
        if (self::$sourceFactory !== null) {
            return self::$sourceFactory;
        }

        if (!\class_exists(SourceFactory::class)) {
            $message = 'Can not find and create instance of %s because the package'
                . ' "phplrt/source" is not available. Please indicate it explicitly'
                . ' using "%s::setSourceFactory()" method.';

            throw new \LogicException(\vsprintf($message, [
                SourceFactoryInterface::class,
                static::class,
            ]));
        }

        return self::$sourceFactory = new SourceFactory();
    }

    /**
     * An alternative factory function of the
     * {@see PositionFactoryInterface::createFromPosition()} method.
     *
     * @param int<1, max> $line expected line value of the position in the
     *        passed source instance
     * @param int<1, max> $column expected column value of the position in the
     *        passed source instance
     *
     * @throws SourceExceptionInterface in case of an error in creating the
     *         source object
     */
    public static function fromPosition(
        $source,
        int $line = PositionInterface::MIN_LINE,
        int $column = PositionInterface::MIN_COLUMN
    ): PositionInterface {
        $factory = self::getPositionFactory();

        if (!$source instanceof ReadableInterface) {
            $sources = self::getSourceFactory();

            $source = $sources->create($source);
        }

        return $factory->createFromPosition($source, $line, $column);
    }

    /**
     * An alternative factory function of the
     * {@see PositionFactoryInterface::createAtStarting()} method.
     */
    public static function start(): PositionInterface
    {
        $factory = self::getPositionFactory();

        return $factory->createAtStarting();
    }

    /**
     * An alternative factory function of the
     * {@see PositionFactoryInterface::createAtEnding()} method.
     *
     * @throws SourceExceptionInterface in case of an error in creating the
     *         source object
     */
    public static function end($source): PositionInterface
    {
        $factory = self::getPositionFactory();

        if (!$source instanceof ReadableInterface) {
            $sources = self::getSourceFactory();

            $source = $sources->create($source);
        }

        return $factory->createAtEnding($source);
    }

    /**
     * An alternative factory function of the
     * {@see PositionFactoryInterface::createFromOffset()} method.
     *
     * @param int<0, max> $offset expected offset of the position in the passed
     *        source instance
     *
     * @throws SourceExceptionInterface in case of an error in creating the
     *         source object
     */
    public static function fromOffset(
        $source,
        int $offset = PositionInterface::MIN_OFFSET
    ): PositionInterface {
        $factory = self::getPositionFactory();

        if (!$source instanceof ReadableInterface) {
            $sources = self::getSourceFactory();

            $source = $sources->create($source);
        }

        return $factory->createFromOffset($source, $offset);
    }
}
