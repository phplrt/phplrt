<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Source\Driver\SourceDriverInterface;
use Phplrt\Source\Driver\SplFileInfoSourceDriver;
use Phplrt\Source\Driver\StreamSourceDriver;
use Phplrt\Source\Driver\StringSourceDriver;
use Phplrt\Source\Exception\NotCreatableException;

final readonly class SourceFactory implements SourceFactoryInterface
{
    /**
     * @var list<SourceDriverInterface>
     */
    private array $drivers;

    /**
     * @param iterable<mixed, SourceDriverInterface> $drivers drivers are
     *        queried in the given order and the first one recognizing the
     *        reference wins
     */
    public function __construct(iterable $drivers = [])
    {
        $this->drivers = \iterator_to_array($drivers, false);
    }

    /**
     * Creates a factory supporting every kind of source reference known to
     * phplrt itself, including the ones that already are a source.
     *
     * @api
     */
    public static function createDefault(): self
    {
        return new self(self::getDefaultDrivers());
    }

    /**
     * Returns default source drivers.
     *
     * @api
     *
     * @return list<SourceDriverInterface>
     */
    public static function getDefaultDrivers(): array
    {
        return [
            new StringSourceDriver(),
            new SplFileInfoSourceDriver(),
            new StreamSourceDriver(),
        ];
    }

    /**
     * @api
     *
     * @throws NotCreatableException in case none of the drivers recognizes
     *         the source argument
     * @throws SourceExceptionInterface in case of source creation exception occurs
     */
    public function create(mixed $source): ReadableInterface
    {
        if ($source instanceof ReadableInterface) {
            return $source;
        }

        foreach ($this->drivers as $driver) {
            $readable = $driver->tryCreate($source);

            if ($readable !== null) {
                return $readable;
            }
        }

        throw NotCreatableException::becauseSourceIsInvalid($source);
    }
}
