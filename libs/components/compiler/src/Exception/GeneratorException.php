<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

/**
 * An error that occurs while a compiled grammar is being written down as code.
 *
 * A grammar that has been read may still say something that cannot be spelled:
 * a piece built at runtime is a value of the process it has been built in and
 * is lost the moment the code is written into a file.
 */
abstract class GeneratorException extends CompilerException {}
