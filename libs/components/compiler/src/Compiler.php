<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Generator\GeneratedOutput;
use Phplrt\Compiler\Generator\OutputGeneratorInterface;
use Phplrt\Compiler\Generator\PhpOutputGenerator;
use Phplrt\Compiler\Loader\ReferenceLoader;
use Phplrt\Compiler\Loader\SyntaxLoaderRegistry;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;

final class Compiler
{
    public readonly ParserBuilder $parser;

    public readonly LexerBuilder $lexer;

    private readonly ReferenceLoader $loader;

    /**
     * The grammar files that have already been read.
     *
     * A grammar reached from several places describes the very same tokens and
     * rules every time, and declaring them twice is an error, so it is read
     * once.
     *
     * @var array<non-empty-string, true>
     */
    private array $loaded = [];

    public function __construct(
        /**
         * Tells which format a grammar is written in and reads it.
         */
        private readonly SyntaxLoaderRegistry $loaders = new SyntaxLoaderRegistry(),
    ) {
        $this->parser = new ParserBuilder();
        $this->lexer = new LexerBuilder();
        $this->loader = new ReferenceLoader($this, $this->loaders);
    }

    /**
     * Reads the given grammar along with every grammar it refers to.
     *
     * @throws CompilerRuntimeException in case of the grammar says something that
     *         cannot be expressed or refers to a grammar that cannot be found
     * @throws RuntimeExceptionInterface in case of the grammar cannot be
     *         recognized
     * @throws SourceExceptionInterface in case of the grammar cannot be read
     */
    public function load(ReadableInterface $source): self
    {
        if (!$this->markAsLoaded($source)) {
            return $this;
        }

        $loader = $this->loaders->selectFor($source);

        /**
         * A reference is read the moment the grammar hands it over, so the
         * declarations of the grammar it points at land exactly where the
         * reference is written.
         */
        foreach ($loader->load($source, $this->parser, $this->lexer) as $reference) {
            $this->loader->load($source, $reference);
        }

        return $this;
    }

    /**
     * Returns {@see true} in case of the given grammar has not been read yet.
     */
    private function markAsLoaded(ReadableInterface $source): bool
    {
        // A grammar written in no file is named by nothing, so there is no
        // way to tell it from another one
        if (!$source instanceof FileInterface) {
            return true;
        }

        $pathname = \realpath($source->pathname);

        if ($pathname === false) {
            $pathname = $source->pathname;
        }

        if (isset($this->loaded[$pathname])) {
            return false;
        }

        return $this->loaded[$pathname] = true;
    }

    public function build(): CompilerResult
    {
        $lexer = $this->lexer->build();
        $parser = $this->parser->build($lexer);

        return new CompilerResult(
            lexer: $lexer,
            parser: $parser,
        );
    }

    /**
     * Writes the grammar that has been read down as source code.
     */
    public function generate(OutputGeneratorInterface $generator = new PhpOutputGenerator()): GeneratedOutput
    {
        return new GeneratedOutput($this->build(), $generator);
    }

    public function getParser(): ParserInterface
    {
        $result = $this->build();

        return $result->parser->toParser(
            lexer: $result->lexer->toLexer(),
        );
    }
}
