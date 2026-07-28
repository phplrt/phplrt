<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Loader;

use Phplrt\Compiler\Syntax\PP\PPLoader;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;

/**
 * Tells which format a grammar is written in.
 *
 * The format is told by the extension of the file the grammar has been read
 * from, which is the only thing a grammar says about itself: nothing inside it
 * names the format it uses. A grammar read from no file at all is therefore
 * read as the newest format there is.
 */
final class SyntaxLoaderRegistry
{
    /**
     * The loaders reading a grammar file, indexed by the extension the file is
     * named with.
     *
     * @var array<non-empty-string, SyntaxLoaderInterface>
     */
    public private(set) array $loaders;

    /**
     * The loader reading a grammar that is written in no file, or in a file
     * named with an extension of no format.
     */
    public private(set) SyntaxLoaderInterface $default;

    /**
     * The extensions a grammar file may be named with, in the order they are
     * tried while a reference is being resolved.
     *
     * @var list<non-empty-string>
     */
    public array $extensions {
        get => \array_keys($this->loaders);
    }

    /**
     * @param array<non-empty-string, SyntaxLoaderInterface>|null $loaders
     */
    public function __construct(
        ?array $loaders = null,
        ?SyntaxLoaderInterface $default = null,
    ) {
        $this->loaders = $loaders ?? $this->createDefaultLoaders();
        $this->default = $default ?? $this->createDefaultLoader();
    }

    private function createDefaultLoader(): SyntaxLoaderInterface
    {
        return new PP3Loader();
    }

    /**
     * @return array<non-empty-string, SyntaxLoaderInterface>
     */
    private function createDefaultLoaders(): array
    {
        return [
            'pp' => new PPLoader(),
            'pp2' => new PP2Loader(),
            'pp3' => new PP3Loader(),
        ];
    }

    /**
     * Returns the loader reading the given grammar.
     *
     * @api
     */
    public function selectFor(ReadableInterface $source): SyntaxLoaderInterface
    {
        $extension = $this->findExtensionBySource($source);

        if ($extension === null) {
            return $this->default;
        }

        return $this->loaders[$extension] ?? $this->default;
    }

    /**
     * Returns the extension the given grammar is named with, or {@see null} in
     * case of the grammar is written in no file or the file bears no
     * extension.
     *
     * @return non-empty-string|null
     */
    private function findExtensionBySource(ReadableInterface $source): ?string
    {
        if (!$source instanceof FileInterface) {
            return null;
        }

        $extension = \pathinfo($source->pathname, \PATHINFO_EXTENSION);

        return $extension === '' ? null : $extension;
    }
}
