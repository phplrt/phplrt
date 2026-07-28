<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\GeneratorException;
use Phplrt\Compiler\Exception\OutputDirectoryException;
use Phplrt\Compiler\Exception\OutputFileException;

/**
 * The code a compiled grammar is written down as.
 *
 * The code is written the moment it is asked for, so the place it is written
 * into may still be told after the compilation is over.
 */
final readonly class GeneratedOutput implements \Stringable
{
    public function __construct(
        /**
         * The result of the compilation the code is written of.
         */
        private CompilerResult $result,
        /**
         * Writes the result down.
         */
        private OutputGeneratorInterface $generator = new PhpOutputGenerator(),
        /**
         * The place the code is written into.
         */
        private OutputContext $context = new OutputContext(),
    ) {}

    /**
     * Returns the output belonging to the given namespace.
     *
     * @param non-empty-string $namespace
     */
    public function withNamespace(string $namespace): self
    {
        return new self($this->result, $this->generator, new OutputContext(
            namespace: $namespace,
            imports: $this->context->imports,
            class: $this->context->class,
        ));
    }

    /**
     * Returns the output declaring the parser under the given name.
     *
     * A parser that is named is declared rather than returned, so the file it
     * is written into is read the way any other class file is.
     *
     * @param non-empty-string $class
     */
    public function withClassName(string $class): self
    {
        return new self($this->result, $this->generator, new OutputContext(
            namespace: $this->context->namespace,
            imports: $this->context->imports,
            class: $class,
        ));
    }

    /**
     * Returns the output referring to the given class by its short name.
     *
     * @param non-empty-string $class
     * @param non-empty-string|null $as the name the class is referred to by, or
     *        {@see null} in case of the class is referred to by the last part
     *        of its own name
     */
    public function withClassImport(string $class, ?string $as = null): self
    {
        return new self($this->result, $this->generator, new OutputContext(
            namespace: $this->context->namespace,
            imports: [...$this->context->imports, new ClassImport($class, $as)],
            class: $this->context->class,
        ));
    }

    /**
     * Writes the code into the given file.
     *
     * @param non-empty-string $pathname
     * @param non-empty-string|null $class the name the parser is declared
     *        under, or {@see null} in case of the parser is returned by the
     *        file instead of being declared
     * @return self the output that has been written into the file
     * @throws GeneratorException in case of the code cannot be written down or
     *         the file cannot be written
     */
    public function save(string $pathname, ?string $class = null): self
    {
        $output = $class === null ? $this : $this->withClassName($class);

        self::createDirectory(\dirname($pathname));

        if (\file_put_contents($pathname, $output->__toString()) === false) {
            throw OutputFileException::becauseFileIsNotWritten($pathname);
        }

        return $output;
    }

    /**
     * @throws OutputDirectoryException
     */
    private static function createDirectory(string $directory): void
    {
        if ($directory === '' || \is_dir($directory) || \mkdir($directory, recursive: true)) {
            return;
        }

        throw OutputDirectoryException::becauseDirectoryIsNotCreated($directory);
    }

    /**
     * @throws GeneratorException in case of the code cannot be written down
     */
    public function __toString(): string
    {
        return $this->generator->generate($this->result, $this->context);
    }
}
