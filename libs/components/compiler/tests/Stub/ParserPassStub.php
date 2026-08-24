<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests\Stub;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Compiler\ParserBuildingContext;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;

final class ParserPassStub implements ParserCompilerPassInterface
{
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void {}
}
