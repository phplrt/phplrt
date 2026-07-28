<?php

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Loader\ReferenceLoader;
use Phplrt\Compiler\Loader\SyntaxLoaderRegistry;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;

final class Compiler implements CompilerInterface
{
    public ParserBuilder $parser;

    public LexerBuilder $lexer;

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

    public function load(ReadableInterface $source): void
    {
        if (!$this->markAsLoaded($source)) {
            return;
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

    public function build(): CompilerResultInterface
    {
        $lexer = $this->lexer->build();
        $parser = $this->parser->build($lexer);

        return new CompilerResult(
            lexer: $lexer,
            parser: $parser,
        );
    }
}
