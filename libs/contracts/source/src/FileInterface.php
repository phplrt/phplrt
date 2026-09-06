<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Source;

/**
 * A source code that is stored in a physical file.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read non-empty-string $pathname The physical pathname of the file
 *                the source is stored in.
 */
interface FileInterface extends ReadableInterface {}
