<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * An error that occurs before a grammar is read, so it is about the compiler
 * itself rather than about what a grammar says.
 */
class CompilerException extends \RuntimeException {}
