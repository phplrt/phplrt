<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer\Exception;

/**
 * An error of the lexical analysis.
 *
 * Every exception thrown by a lexer MUST implement this interface.
 */
interface LexerExceptionInterface extends \Throwable {}
