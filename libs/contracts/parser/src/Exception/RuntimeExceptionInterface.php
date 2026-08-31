<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Parser\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An error that occurs after the syntax analysis has been started and
 * indicates a problem in the analyzed source.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read ReadableInterface $source The source the error occurred in.
 * @property-read TokenInterface $token The token the error occurred on.
 * @property-read int<0, max>|null $length The size of the source fragment the
 *                error occurred in, in bytes, or {@see null} in case the size
 *                is not known.
 *
 *                The fragment starts at the offset of the token the error
 *                occurred on and MAY be as large as the whole grammar rule the
 *                analysis has failed on.
 */
interface RuntimeExceptionInterface extends ParserExceptionInterface {}
