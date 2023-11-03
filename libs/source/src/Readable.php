<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\ReadableInterface;

abstract class Readable implements ReadableInterface
{
    use SourceFactoryTrait;
}
