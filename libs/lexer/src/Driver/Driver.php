<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Lexer\Compiler\CompilerInterface;

abstract class Driver implements DriverInterface
{
    /**
     * @var non-empty-string|null
     */
    private ?string $pattern = null;

    private CompilerInterface $compiler;

    public function __construct(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    public function reset(): void
    {
        $this->pattern = null;
    }

    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return non-empty-string
     */
    protected function getPattern(array $tokens): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->compile($tokens);
        }

        return $this->pattern;
    }

    /**
     * @param array<non-empty-string, non-empty-string> $tokens
     * @return non-empty-string
     */
    protected function compile(array $tokens): string
    {
        return $this->compiler->compile($tokens);
    }
}
