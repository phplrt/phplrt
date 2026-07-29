<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Loader;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Exception\GrammarNotFoundException;
use Phplrt\Compiler\Exception\IncludeException;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\File;

/**
 * Reads the grammar a reference points at.
 *
 * A reference is written the way one grammar file refers to another, so it is
 * relative and its extension may be omitted: what it stands for is decided
 * here, by looking for the file next to the grammar the reference is written
 * in.
 */
final readonly class ReferenceLoader
{
    /**
     * The directory a reference is relative to in case of the grammar it is
     * written in has been read from no file at all.
     *
     * @var non-empty-string
     */
    private const string DIRECTORY_CURRENT = '.';

    public function __construct(
        private Compiler $context,
        private SyntaxLoaderRegistry $loaders,
    ) {}

    /**
     * @throws CompilerRuntimeException
     */
    public function load(ReadableInterface $source, GrammarReference $reference): void
    {
        $pathname = $this->findPathname($source, $reference);

        try {
            $this->context->load(new File($pathname));
        } catch (\Throwable $e) {
            /**
             * Whatever has gone wrong has gone wrong in another grammar, so
             * the reference that has asked for it is reported along with it:
             * the two are printed one after another, the way a stack of
             * includes is read.
             */
            throw IncludeException::fromThrowable($source, $reference, $e);
        }
    }

    /**
     * Returns the pathname of the grammar file the reference points at.
     *
     * @return non-empty-string
     * @throws GrammarNotFoundException
     */
    private function findPathname(ReadableInterface $source, GrammarReference $reference): string
    {
        $pathname = $this->calculateDirectory($source) . '/' . \trim($reference->target, '/');

        if (\is_file($pathname)) {
            return $pathname;
        }

        // A reference is allowed to omit the extension, so every format there
        // is gets tried in turn
        foreach ($this->loaders->extensions as $extension) {
            if (\is_file($pathname . '.' . $extension)) {
                return $pathname . '.' . $extension;
            }
        }

        throw GrammarNotFoundException::becauseGrammarIsNotFound($source, $reference);
    }

    /**
     * Returns the directory a reference written in the given grammar is
     * relative to.
     *
     * @return non-empty-string
     */
    private function calculateDirectory(ReadableInterface $source): string
    {
        $directory = $source instanceof FileInterface
            ? \dirname($source->pathname)
            : \getcwd();

        if ($directory === false) {
            return self::DIRECTORY_CURRENT;
        }

        return \str_replace('\\', '/', $directory);
    }
}
