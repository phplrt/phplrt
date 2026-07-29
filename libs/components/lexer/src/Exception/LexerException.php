<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;

/**
 * An error that occurs before the analysis starts, so it is about the lexer
 * itself rather than about the source code it reads.
 */
class LexerException extends \RuntimeException implements LexerExceptionInterface {}
