<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotCreatableException;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Provider\PsrStreamSourceProvider;
use Phplrt\Source\Provider\SourceProviderInterface;
use Phplrt\Source\Provider\SplFileInfoSourceProvider;
use Phplrt\Source\Provider\StreamSourceProvider;
use Phplrt\Source\Provider\TextSourceProvider;

final class SourceFactory implements SourceFactoryInterface
{
    /**
     * Default chunk size value.
     *
     * @var int<1, max>
     */
    public const DEFAULT_CHUNK_SIZE = 65536;

    /**
     * Default hashing algorithm value.
     *
     * @var non-empty-string
     */
    public const DEFAULT_HASH_ALGO = 'md5';

    /**
     * Default name of the temporary streams.
     *
     * @var non-empty-string
     */
    public const DEFAULT_TEMP_STREAM = 'php://memory';

    /**
     * @var non-empty-string
     * @psalm-readonly-allow-private-mutation
     */
    public string $algo = self::DEFAULT_HASH_ALGO;

    /**
     * @var non-empty-string
     * @psalm-readonly-allow-private-mutation
     */
    public string $temp = self::DEFAULT_TEMP_STREAM;

    /**
     * @var int<1, max>
     * @psalm-readonly-allow-private-mutation
     */
    public int $chunkSize = self::DEFAULT_CHUNK_SIZE;

    /**
     * @var list<SourceProviderInterface>
     */
    private array $providers = [];

    /**
     * @param non-empty-string $algo Hashing algorithm for the sources.
     * @param non-empty-string $temp The name of the temporary stream, which is
     *        used as a resource during the reading of the source.
     * @param int<1, max> $chunkSize The chunk size used while non-blocking
     *        reading the file inside the {@see \Fiber} context.
     * @param list<SourceProviderInterface> $providers List of source providers.
     */
    public function __construct(
        string $algo = self::DEFAULT_HASH_ALGO,
        string $temp = self::DEFAULT_TEMP_STREAM,
        int $chunkSize = self::DEFAULT_CHUNK_SIZE,
        iterable $providers = []
    ) {
        assert($algo !== '', 'Hashing algorithm name must not be empty');
        assert($temp !== '', 'Temporary stream name must not be empty');
        assert($chunkSize >= 1, 'Chunk size must be greater than 0');

        $this->chunkSize = $chunkSize;
        $this->temp = $temp;
        $this->algo = $algo;

        $this->providers = [
            ...$providers,
            new PsrStreamSourceProvider($this),
            new SplFileInfoSourceProvider($this),
            new StreamSourceProvider($this),
            new TextSourceProvider($this),
        ];
    }

    /**
     * Appends a new provider to the END of providers list.
     *
     * @psalm-immutable
     */
    public function withAppendedProvider(SourceProviderInterface $provider): self
    {
        $self = clone $this;
        $self->providers[] = $provider;

        return $self;
    }

    /**
     * Prepends a new provider to the START of providers list.
     *
     * @psalm-immutable
     */
    public function withPrependedProvider(SourceProviderInterface $provider): self
    {
        $self = clone $this;
        $self->providers = [$provider, ...$this->providers];

        return $self;
    }

    public function create($source): ReadableInterface
    {
        foreach ($this->providers as $provider) {
            $readable = $provider->create($source);

            if ($readable instanceof ReadableInterface) {
                return $readable;
            }
        }

        if ($source instanceof ReadableInterface) {
            return $source;
        }

        throw NotCreatableException::fromInvalidType($source);
    }

    public function createFromString(string $content = '', string $name = null): ReadableInterface
    {
        assert($name !== '', 'Name must not be empty');

        if ($name === null) {
            return new Source($content, $this->algo, $this->temp);
        }

        return new VirtualFile($name, $content, $this->algo, $this->temp);
    }

    public function createFromFile(string $filename): FileInterface
    {
        if (!\is_file($filename)) {
            throw NotFoundException::fromInvalidPathname($filename);
        }

        if (!\is_readable($filename)) {
            throw NotReadableException::fromOpeningFile($filename);
        }

        return new File($filename, $this->algo, $this->chunkSize);
    }

    /**
     * @throws NotReadableException
     */
    public function createFromStream($stream, string $name = null): ReadableInterface
    {
        assert($name !== '', 'Name must not be empty');

        if (!\is_resource($stream)) {
            throw NotReadableException::fromInvalidResource($stream);
        }

        if (\get_resource_type($stream) !== 'stream') {
            throw NotReadableException::fromInvalidStream($stream);
        }

        if ($name === null) {
            return new Stream($stream, $this->algo, $this->chunkSize);
        }

        return new VirtualStreamingFile($name, $stream, $this->algo, $this->chunkSize);
    }
}
