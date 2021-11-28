<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\Internal\ContentReader\ContentReader;
use Phplrt\Source\Internal\ContentReader\StreamContentReader;
use Phplrt\Source\Internal\StreamReader\ContentStreamReader;
use Phplrt\Source\Internal\StreamReader\StreamReader;
use Phplrt\Source\Internal\Util;

/**
 * @psalm-type SourceResolver = callable(mixed): ?ReadableInterface
 */
final class Factory implements FactoryInterface
{
    /**
     * @var non-empty-string
     */
    private const ERROR_INVALID_SOURCE_TYPE = 'Unrecognized readable file type "%s"';

    /**
     * @var non-empty-string
     */
    private const ERROR_NON_RESOURCE_STREAM = 'First argument must be a valid resource stream, but %s given';

    /**
     * @var non-empty-string
     */
    private const ERROR_CLOSED_RESOURCE_STREAM = 'Can not open for reading already closed resource';

    /**
     * @var FactoryInterface|null
     */
    private static ?FactoryInterface $instance = null;

    /**
     * @var array<SourceResolver>
     */
    private array $resolvers = [];

    /**
     * @return FactoryInterface
     */
    public static function getInstance(): FactoryInterface
    {
        return self::$instance ??= new self();
    }

    /**
     * @param FactoryInterface|null $factory
     * @return void
     */
    public static function setInstance(?FactoryInterface $factory): void
    {
        self::$instance = $factory;
    }

    /**
     * @param SourceResolver $resolver
     * @return $this
     */
    public function extend(callable $resolver): self
    {
        $this->resolvers[] = $resolver;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function fromSource(string $source, string $pathname = null): ReadableInterface
    {
        $stream = new ContentStreamReader($source);
        $content = new ContentReader($source);

        if ($pathname !== null) {
            return new File($pathname, $stream, $content);
        }

        return new Readable($stream, $content);
    }

    /**
     * {@inheritDoc}
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function create(mixed $source): ReadableInterface
    {
        return match (true) {
            $source instanceof ReadableInterface => $source,
            $source instanceof \SplFileInfo => $this->fromSplFileInfo($source),
            \is_string($source) => $this->fromSource($source),
            \is_resource($source) => $this->fromResourceStream($source),
            default => $this->createFromResolvers($source),
        };
    }

    /**
     * @param mixed $source
     * @return ReadableInterface
     */
    private function createFromResolvers(mixed $source): ReadableInterface
    {
        foreach ($this->resolvers as $resolver) {
            if (($result = $resolver($source)) instanceof ReadableInterface) {
                return $result;
            }
        }

        throw new \InvalidArgumentException(
            \sprintf(self::ERROR_INVALID_SOURCE_TYPE, \get_debug_type($source))
        );
    }

    /**
     * {@inheritDoc}
     * @throws NotFoundException
     * @throws NotReadableException
     * @psalm-suppress ArgumentTypeCoercion SplFileInfo's pathname can not be empty
     */
    public function fromSplFileInfo(\SplFileInfo $info): FileInterface
    {
        return $this->fromPathname($info->getPathname());
    }

    /**
     * {@inheritDoc}
     * @throws NotFoundException
     * @throws NotReadableException
     */
    public function fromPathname(string $pathname): FileInterface
    {
        File::assertValidPathname($pathname);

        return new File($pathname);
    }

    /**
     * {@inheritDoc}
     * @throws NotReadableException
     */
    public function fromResourceStream(mixed $stream, string $pathname = null): ReadableInterface
    {
        assert(Util::isStream($stream), new \InvalidArgumentException(
            \sprintf(self::ERROR_NON_RESOURCE_STREAM, \get_debug_type($stream))
        ));

        assert(Util::isNonClosedStream($stream), new NotReadableException(
            self::ERROR_CLOSED_RESOURCE_STREAM
        ));

        if ($pathname !== null) {
            return new File(
                pathname: $pathname,
                stream: new StreamReader($stream),
                content: new StreamContentReader($stream)
            );
        }

        return new Readable(
            stream: new StreamReader($stream),
            content: new StreamContentReader($stream)
        );
    }
}
