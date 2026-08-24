<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP2;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Syntax\Common\PPLoader;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Reads a PP2 grammar file into the lexer and the parser it describes.
 *
 * The format says what a token does by naming the state it ends up in, and
 * carries a single setting: the rule the analysis starts at.
 */
final class PP2Loader extends PPLoader
{
    protected function createParser(): ParserInterface
    {
        return new PP2Parser();
    }

    protected function loadAction(
        TokenDefinition $definition,
        TokenDeclaration $declaration,
        string $state,
        ReadableInterface $source,
    ): void {
        $this->loadTransition($definition, $declaration, $state, $source);
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadPragma(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void {
        if ($declaration->name !== self::PRAGMA_ROOT) {
            throw UnsupportedPragmaException::becausePragmaIsNotSupported($source, $declaration);
        }

        $this->loadRootPragma($declaration, $source, $parser);
    }
}
