<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Node;

/**
 * A top level element of a grammar file.
 *
 * @phpstan-sealed IncludeDeclaration|LexerDeclaration|PragmaDeclaration|RuleDeclaration|TokenDeclaration
 */
abstract readonly class Declaration extends Node {}
