<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Tests\Stub;

use Psr\Log\AbstractLogger;

final class InMemoryLogger extends AbstractLogger
{
    /**
     * The messages that have been reported, each one prefixed by its level.
     *
     * @var list<non-empty-string>
     */
    public private(set) array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $result = (string) $message;

        foreach ($context as $name => $value) {
            $result = \str_replace(
                '{' . $name . '}',
                \is_scalar($value) ? (string) $value : \get_debug_type($value),
                $result,
            );
        }

        $this->records[] = \sprintf('%s: %s', (string) $level, $result);
    }
}
