<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(Source::class, false)) {
    return \class_alias(StringSource::class, Source::class);
}

/**
 * @deprecated since phplrt 4.0 and will be removed in 5.0,
 *             please use {@see StringSource} instead.
 */
final class Source extends StringSource {}
