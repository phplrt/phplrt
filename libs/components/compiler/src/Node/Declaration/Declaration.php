<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Node\Declaration;

use Phplrt\Compiler\Node\Node;

/**
 * A top level element of a grammar file.
 *
 * @phpstan-sealed FragmentDeclaration|IncludeDeclaration|LexerDeclaration|PragmaDeclaration|RuleDeclaration|TokenDeclaration
 *
 * @readonly
 */
abstract class Declaration extends Node {}
