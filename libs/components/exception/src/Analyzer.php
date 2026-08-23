<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface as ParserRuntimeExceptionInterface;
use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Analysis\FailureInterval;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Position\Position;
use Phplrt\Position\PositionFactory;
use Phplrt\Source\FileSource;
use Phplrt\Source\StringSource;

/**
 * Tells everything that is known about an error: the source it occurred in,
 * the place inside that source and the fragment it covers.
 */
final readonly class Analyzer
{
    public function __construct(
        private PositionFactoryInterface $positions = new PositionFactory(),
    ) {}

    /**
     * Returns the information about the given error, along with the one about
     * every error that has led to it.
     *
     * @throws SourceExceptionInterface in case the data of the source an
     *         error occurred in cannot be read
     */
    public function analyze(\Throwable $e): FailureResult
    {
        // collect exception inheritance chain
        $chain = [];
        do {
            $chain[] = $e;
        } while (($e = $e->getPrevious()) !== null);

        // build the result in reverse order
        $result = $this->describe(\array_pop($chain));

        while ($chain !== []) {
            $result = $this->describe(\array_pop($chain), $result);
        }

        return $result;
    }

    /**
     * @throws SourceExceptionInterface in case the data of the source the
     *         given error occurred in cannot be read
     */
    private function describe(\Throwable $e, ?FailureResult $previous = null): FailureResult
    {
        $source = $this->createSource($e);
        $interval = $this->createInterval($e);

        return new FailureResult(
            class: $e::class,
            message: $e->getMessage(),
            source: $source,
            position: $this->createPosition($e, $source, $interval),
            level: FailureLevel::fromException($e),
            interval: $interval,
            previous: $previous,
        );
    }

    /**
     * Returns the source the given error occurred in.
     */
    private function createSource(\Throwable $e): ReadableInterface
    {
        if ($e instanceof ParserRuntimeExceptionInterface
            || $e instanceof LexerRuntimeExceptionInterface) {
            return $e->source;
        }

        if (($pathname = $e->getFile()) === '') {
            return StringSource::createEmpty();
        }

        return FileSource::createFromPathname($pathname);
    }

    /**
     * Returns the fragment of the source the given error occurred in, or
     * {@see null} in case the error tells nothing about the size of it.
     */
    private function createInterval(\Throwable $e): ?FailureInterval
    {
        if ($e instanceof ParserRuntimeExceptionInterface) {
            return new FailureInterval(
                offset: $e->token->offset,
                length: \max(0, $e->length ?? $e->token->size),
            );
        }

        if ($e instanceof LexerRuntimeExceptionInterface) {
            return new FailureInterval(
                offset: $e->token->offset,
                length: $e->token->size,
            );
        }

        return null;
    }

    /**
     * Returns the place inside the given source the given error occurred at.
     *
     * @throws SourceExceptionInterface in case the data of the given source
     *         cannot be read
     */
    private function createPosition(\Throwable $e, ReadableInterface $source, ?FailureInterval $interval): PositionInterface
    {
        if ($interval !== null) {
            return $this->positions->createFromOffset(
                source: $source,
                offset: $interval->offset,
            );
        }

        return new Position(\max(PositionInterface::MIN_LINE, $e->getLine()));
    }
}
