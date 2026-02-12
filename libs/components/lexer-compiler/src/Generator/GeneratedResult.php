<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

readonly class GeneratedResult implements \Stringable
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $result,
    ) {}

    /**
     * @param non-empty-string $pathname
     */
    private static function prepareOutputDirectory(string $pathname): void
    {
        $directory = \dirname($pathname);

        if (\is_dir($directory) || \mkdir($directory, recursive: true)) {
            return;
        }

        throw new \RuntimeException(\sprintf('Directory "%s" was not created', $directory));
    }

    /**
     * @param non-empty-string $pathname
     */
    public function save(string $pathname): void
    {
        self::prepareOutputDirectory($pathname);

        if (\file_put_contents($pathname, $this->result) !== false) {
            return;
        }

        throw new \RuntimeException(\sprintf('Could not write generated result into "%s"', $pathname));
    }

    public function __toString(): string
    {
        return $this->result;
    }
}
