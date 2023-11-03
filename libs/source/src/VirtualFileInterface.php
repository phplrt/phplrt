<?php

declare(strict_types=1);

namespace Phplrt\Source;

use Phplrt\Contracts\Source\FileInterface;

/**
 * This interface means that this source object was created with a known
 * name and may not exist physically.
 */
interface VirtualFileInterface extends FileInterface {}
