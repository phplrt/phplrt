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
        /**
         * Finds the contracts the code loads before it refers to them.
         */
        private ContractsPreloader $contracts = new ContractsPreloader(),
        /**
         * Whether the contracts of the runtime are loaded by the code itself.
         */
        private bool $preloadContracts = true,
    ) {}

    /**
     * Returns the output belonging to the given namespace.
     *
     * @param non-empty-string|null $namespace
     */
    public function withNamespaceName(?string $namespace): self
    {
        return $this->withContext(new OutputContext(
            namespace: $namespace,
            imports: $this->context->imports,
            class: $this->context->class,
            php: $this->context->php,
        ));
    }

    /**
     * Returns the output declaring the parser under the given name.
     *
     * A parser that is named is declared rather than returned, so the file it
     * is written into is read the way any other class file is.
     *
     * @param non-empty-string|null $class
     */
    public function withClassName(?string $class): self
    {
        return $this->withContext(new OutputContext(
            namespace: $this->context->namespace,
            imports: $this->context->imports,
            class: $class,
            php: $this->context->php,
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
        return $this->withContext(new OutputContext(
            namespace: $this->context->namespace,
            imports: [...$this->context->imports, new ClassImport($class, $as)],
            class: $this->context->class,
            php: $this->context->php,
        ));
    }

    /**
     * Returns new output with the PHP target version
     *
     * @api
     */
    public function withTargetPhpVersion(TargetPhpVersion $version): self
    {
        return $this->withContext(new OutputContext(
            namespace: $this->context->namespace,
            imports: $this->context->imports,
            class: $this->context->class,
            php: $version,
        ));
    }

    /**
     * Returns the output leaving the contracts of the runtime to be loaded the
     * moment they are referred to.
     */
    public function withoutContractsPreloading(): self
    {
        return new self(
            result: $this->result,
            generator: $this->generator,
            context: $this->context,
            contracts: $this->contracts,
            preloadContracts: false,
        );
    }

    private function withContext(OutputContext $context): self
    {
        return new self(
            result: $this->result,
            generator: $this->generator,
            context: $context,
            contracts: $this->contracts,
            preloadContracts: $this->preloadContracts,
        );
    }


    /**
     * Writes the code into the given file.
     *
     * @param non-empty-string $pathname
     * @throws GeneratorException in case of the code cannot be written down or
     *         the file cannot be written
     */
    public function save(string $pathname): self
    {
        self::createDirectory(\dirname($pathname));

        if (\file_put_contents($pathname, (string) $this) === false) {
            throw OutputFileException::becauseFileIsNotWritten($pathname);
        }

        return $this;
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
        $context = clone $this->context;

        if ($this->preloadContracts) {
            $context->includes = $this->contracts->createIncludes();
        }

        return $this->generator->generate($this->result, $context);
    }
}
