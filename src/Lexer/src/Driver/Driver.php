<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Lexer\Internal\Regex\CompilerInterface;

abstract class Driver implements DriverInterface
{
    /**
     * @var string|null
     */
    private ?string $pattern = null;

    /**
     * @var CompilerInterface
     */
    private CompilerInterface $compiler;

    /**
     * State constructor.
     *
     * @param CompilerInterface $compiler
     */
    public function __construct(CompilerInterface $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->pattern = null;
    }

    /**
     * @return CompilerInterface
     */
    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * @param array $tokens
     * @return string
     */
    protected function getPattern(array $tokens): string
    {
        if ($this->pattern === null) {
            $this->pattern = $this->compile($tokens);
        }

        return $this->pattern;
    }

    /**
     * @param array $tokens
     * @return string
     */
    protected function compile(array $tokens): string
    {
        return $this->compiler->compile($tokens);
    }
}
