<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Factory\FileSourceFactory;
use Phplrt\Source\Factory\StreamSourceFactory;
use Phplrt\Source\Factory\StringSourceFactory;

/**
 * @template-implements SourceFactoryInterface<mixed>
 */
final class SourceFactory implements SourceFactoryInterface
{
    /**
     * Contains default factory implementation
     */
    private static self $default;

    /**
     * @var list<SourceFactoryInterface>
     */
    private iterable $factories {
        get {
            /** @phpstan-ignore-next-line : false-positive */
            if (\is_array($this->factories) && \array_is_list($this->factories)) {
                return $this->factories;
            }

            /** @phpstan-ignore-next-line : false-positive */
            return $this->factories = \iterator_to_array($this->factories, false);
        }
    }

    public static function default(): self
    {
        return self::$default ??= new self([
            new StringSourceFactory(),
            new FileSourceFactory(),
            new StreamSourceFactory(),
        ]);
    }

    /**
     * @param iterable<mixed, SourceFactoryInterface> $factories
     */
    public function __construct(iterable $factories = [])
    {
        $this->factories = \iterator_to_array($factories, false);
    }

    /**
     * @return SourceFactoryInterface<mixed>|null
     */
    private function select(mixed $source): ?SourceFactoryInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($source)) {
                return $factory;
            }
        }

        return null;
    }

    public function supports(mixed $source): bool
    {
        return $this->select($source) !== null;
    }

    public function create(mixed $source, ?string $virtualPathname = null): ReadableInterface
    {
        if ($source instanceof ReadableInterface) {
            return $source;
        }

        $factory = $this->select($source);

        if ($factory === null) {
            throw NotCreatableException::becauseSourceIsUnsupported($source);
        }

        return $factory->create($source, $virtualPathname);
    }
}
