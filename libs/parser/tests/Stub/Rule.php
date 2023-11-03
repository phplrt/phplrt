<?php

declare(strict_types=1);

namespace Phplrt\Parser\Tests\Stub;

use Phplrt\Parser\Grammar\RuleInterface;

class Rule implements RuleInterface
{
    public static function new(): self
    {
        return new self();
    }

    public function getTerminals(array $rules): iterable
    {
        return [];
    }
}
