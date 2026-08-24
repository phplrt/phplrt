<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests\Stub;

use Phplrt\Lexer\Builder\Compiler\LexerBuildingContext;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;

final class LexerPassStub implements LexerCompilerPassInterface
{
    public function process(LexerBuildingContext $context): void {}
}
