<?php

declare(strict_types=1);

namespace Phplrt\Source;

if (!\class_exists(File::class, false)) {
    \class_alias(FileSource::class, File::class);
}

if (!\class_exists(Source::class, false)) {
    \class_alias(StringSource::class, Source::class);
}

if (!\class_exists(Stream::class, false)) {
    \class_alias(ResourceSource::class, Stream::class);
}

if (!\class_exists(VirtualFile::class, false)) {
    \class_alias(VirtualSource::class, VirtualFile::class);
}

if (!\class_exists(VirtualStreamingFile::class, false)) {
    \class_alias(VirtualSource::class, VirtualStreamingFile::class);
}
