<?php

declare(strict_types=1);

namespace Phplrt\Source;

/**
 * This interface means that it is preferable to read the source entirely as
 * text (for example, the source's content is already in memory), instead of
 * reading the stream.
 */
interface PreferContentReadingInterface {}
