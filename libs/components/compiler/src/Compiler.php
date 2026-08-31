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
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Contracts\Source\SourceFactoryInterface;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Parser\Parser;
use Phplrt\Source\SourceFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Compiler implements LoggerAwareInterface
{
    public readonly ParserBuilder $parser;

    public readonly LexerBuilder $lexer;

    /**
     * Reports what happens to the grammar while it is read and compiled.
     *
     * @phpstan-readonly-allow-private-mutation
     */
    public LoggerInterface $logger;

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

    private readonly SourceFactoryInterface $sources;

    public function __construct(
        /**
         * Tells which format a grammar is written in and reads it.
         */
        private readonly SyntaxLoaderRegistry $loaders = new SyntaxLoaderRegistry(),
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::createDefault();
        $this->logger = new NullLogger();
        $this->parser = new ParserBuilder();
        $this->lexer = new LexerBuilder();
        $this->loader = new ReferenceLoader($this, $this->loaders);
    }

    /**
     * Registers the logger the compilation reports to, along with the builders
     * it is done by.
     *
     * @api
     */
    #[\Override]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        $this->parser->setLogger($logger);
        $this->lexer->setLogger($logger);
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
    public function load(mixed $source): self
    {
        $source = $this->sources->create($source);

        if (!$this->markAsLoaded($source)) {
            $this->logger->debug('Grammar {grammar} has already been read', [
                'grammar' => self::printSource($source),
            ]);

            return $this;
        }

        $this->logger->info('Reading the {grammar} grammar', [
            'grammar' => self::printSource($source),
        ]);

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
     * Returns the name a grammar is reported under.
     *
     * @return non-empty-string
     */
    private static function printSource(ReadableInterface $source): string
    {
        if ($source instanceof FileInterface) {
            return $source->pathname;
        }

        return 'in-memory';
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
        $this->logger->info('Compiling the grammar that has been read');

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

    /**
     * The parser is returned as the implementation rather than as the contract,
     * so that what it can do beyond reading a source in full is reachable.
     */
    public function getParser(): Parser
    {
        $result = $this->build();

        return $result->parser->toParser(
            lexer: $result->lexer->toLexer(),
        );
    }
}
