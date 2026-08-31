<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(VirtualFile::class, false)) {
    return \class_alias(VirtualSource::class, VirtualFile::class);
}

/**
 * @deprecated since phplrt 4.0 and will be removed in 5.0,
 *             please use {@see VirtualSource} instead.
 * @phpstan-ignore class.extendsFinalByPhpDoc
 */
final class VirtualFile extends VirtualSource {}
