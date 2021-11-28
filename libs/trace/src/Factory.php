<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Trace;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Trace\TraceInterface;
use Phplrt\Source\Factory as SourceFactory;
use Phplrt\Source\FactoryInterface as SourceFactoryInterface;
use Phplrt\Position\FactoryInterface as PositionFactoryInterface;
use Phplrt\Position\Factory as PositionFactory;

final class Factory implements FactoryInterface
{
    /**
     * @var FactoryInterface|null
     */
    private static ?FactoryInterface $instance = null;

    /**
     * @param SourceFactoryInterface $source
     * @param PositionFactoryInterface $position
     */
    public function __construct(
        private readonly SourceFactoryInterface $source = new SourceFactory(),
        private readonly PositionFactoryInterface $position = new PositionFactory(),
    ) {
    }

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
     * {@inheritDoc}
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function create(int $depth = 0): TraceInterface
    {
        $trace = new Trace();

        foreach (\debug_backtrace(0) as $index => $item) {
            if ($index <= $depth) {
                continue;
            }

            $source = $this->file($item['file'] ?? null);
            $position = $this->position->fromLineAndColumn($source, $item['line'] ?? 1);

            switch (true) {
                case isset($item['class']):
                    $trace->addMethod($source, $position, $item['class'], $item['function'], $item['args'] ?? []);
                    break;

                default:
                    $trace->addFunction($source, $position, $item['function'], $item['args'] ?? []);
                    break;
            }
        }

        return $trace;
    }

    /**
     * @param non-empty-string|null $name
     * @return ReadableInterface
     */
    private function file(?string $name): ReadableInterface
    {
        if ($name === null) {
            return $this->source->create('');
        }

        return \is_file($name)
            ? $this->source->fromPathname($name)
            : $this->source->fromSource('', $name)
        ;
    }
}
