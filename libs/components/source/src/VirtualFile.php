<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(VirtualFile::class, false)) {
    return \class_alias(VirtualSource::class, VirtualFile::class);
}

/**
 * @final please do not inherit from this class
 * @deprecated since phplrt 4.0 and will be removed in 5.0,
 *             please use {@see VirtualSource} instead.
 */
final class VirtualFile extends VirtualSource {}
