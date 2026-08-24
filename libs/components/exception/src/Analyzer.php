<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Position\PositionFactoryInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Exception\Analysis\FailureLevel;
use Phplrt\Exception\Analysis\FailureResult;
use Phplrt\Exception\Analysis\Internal\FailureLocation;
use Phplrt\Exception\Analysis\Internal\FailureLocatorInterface;
use Phplrt\Exception\Analysis\Internal\LexerFailureLocator;
use Phplrt\Exception\Analysis\Internal\ParserFailureLocator;
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
    /**
     * The locators telling where an error has occurred, queried in the given
     * order.
     *
     * @var list<FailureLocatorInterface>
     */
    private array $locators;

    public function __construct(
        /**
         * The factory telling which line and column of the source an error
         * has occurred at.
         */
        private PositionFactoryInterface $positions = new PositionFactory(),
    ) {
        // A syntax error tells the size of its own, which is more precise
        // than the one of the token it has been raised on.
        $this->locators = [
            new ParserFailureLocator(),
            new LexerFailureLocator(),
        ];
    }

    /**
     * Returns the information about the given error, along with the one about
     * every error that has led to it.
     *
     * @throws SourceExceptionInterface in case the data of the source an
     *         error occurred in cannot be read
     */
    public function analyze(\Throwable $e): FailureResult
    {
        $chain = $this->collect($e);

        // The chain is walked from its end, so that every error is described
        // after the one it refers to as the previous.
        $result = $this->describe(\array_pop($chain));

        while ($chain !== []) {
            $result = $this->describe(\array_pop($chain), $result);
        }

        return $result;
    }

    /**
     * Returns the given error along with every error that has led to it.
     *
     * @return non-empty-list<\Throwable>
     */
    private function collect(\Throwable $e): array
    {
        $result = [$e];

        for ($previous = $e->getPrevious(); $previous !== null; $previous = $previous->getPrevious()) {
            $result[] = $previous;
        }

        return $result;
    }

    /**
     * Returns everything the given error tells about itself.
     *
     * @throws SourceExceptionInterface in case the data of the source the
     *         given error occurred in cannot be read
     */
    private function describe(\Throwable $e, ?FailureResult $previous = null): FailureResult
    {
        $location = $this->locate($e);

        return new FailureResult(
            class: $e::class,
            message: $e->getMessage(),
            source: $location->source,
            position: $this->createPosition($e, $location),
            level: FailureLevel::fromException($e),
            interval: $location->interval,
            previous: $previous,
        );
    }

    /**
     * Returns the place the given error has occurred at.
     */
    private function locate(\Throwable $e): FailureLocation
    {
        foreach ($this->locators as $locator) {
            $result = $locator->tryLocate($e);

            if ($result !== null) {
                return $result;
            }
        }

        return $this->createThrowLocation($e);
    }

    /**
     * Returns the place an error telling nothing of its own has been thrown
     * from.
     */
    private function createThrowLocation(\Throwable $e): FailureLocation
    {
        $pathname = $e->getFile();

        return new FailureLocation($pathname === ''
            ? StringSource::createEmpty()
            : FileSource::createFromPathname($pathname));
    }

    /**
     * Returns the place inside the source the given error occurred at.
     *
     * @throws SourceExceptionInterface in case the data of the source cannot
     *         be read
     */
    private function createPosition(\Throwable $e, FailureLocation $location): PositionInterface
    {
        if ($location->interval === null) {
            return new Position(\max(PositionInterface::MIN_LINE, $e->getLine()));
        }

        return $this->positions->createFromOffset($location->source, $location->interval->offset);
    }
}
