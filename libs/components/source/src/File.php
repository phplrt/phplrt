<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(File::class, false)) {
    return \class_alias(FileSource::class, File::class);
}

/**
 * @deprecated since phplrt 4.0 and will be removed in 5.0,
 *             please use {@see FileSource} instead.
 */
final class File extends FileSource {}
