<?php

declare(strict_types=1);

namespace Phplrt\Parser\Environment;

use Phplrt\Parser\Parser;

interface SelectorInterface
{
    /**
     * Disables environment restrictions for the {@see Parser} to work.
     */
    public function prepare(): void;

    /**
     * Resets all environment settings/restrictions to default.
     */
    public function rollback(): void;
}
