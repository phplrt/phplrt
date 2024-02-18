<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Compiler\Ast\Node;
use Phplrt\Contracts\Parser\ParserInterface;

/**
 * @template-extends ParserInterface<Node>
 */
interface GrammarInterface extends ParserInterface {}
