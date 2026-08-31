<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(Stream::class, false)) {
    return \class_alias(ResourceSource::class, Stream::class);
}

/**
 * @deprecated since phplrt 4.0 and will be removed in 5.0,
 *             please use {@see ResourceSource} instead.
 */
final class Stream extends ResourceSource {}
