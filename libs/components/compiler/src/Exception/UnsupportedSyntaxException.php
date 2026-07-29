<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * Reports what a grammar file says that the compiler cannot express.
 */
abstract class UnsupportedSyntaxException extends CompilerRuntimeException {}
