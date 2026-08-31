<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Exception;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * An error that occurs after the lexical analysis has been started and
 * indicates a problem in the analyzed source.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read ReadableInterface $source The source the error occurred in.
 * @property-read TokenInterface $token The token the error occurred on.
 */
interface RuntimeExceptionInterface extends LexerExceptionInterface {}
