<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests\Stub;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;

/**
 * A pass a grammar registers by its name, doing nothing at all.
 */
final class ParserPassStub implements ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void {}
}
