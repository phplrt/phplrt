<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Expr;

use Phplrt\Contracts\Source\FileInterface;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class IncludeExpr extends Expression
{
    /**
     * @var non-empty-string
     */
    private string $target;

    /**
     * @param non-empty-string $file
     */
    public function __construct(string $file)
    {
        $file = \trim($file, '"\'');

        if ($file === '') {
            throw new \InvalidArgumentException('File include pathname must not be empty');
        }

        if (\trim($file, '/') === '') {
            throw new \InvalidArgumentException('File include must contain filename');
        }

        $this->target = $file;
    }

    /**
     * @return non-empty-string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @return non-empty-string
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function getTargetPathname(): string
    {
        if (!$this->file instanceof FileInterface) {
            return $this->target;
        }

        $directory = \dirname($this->file->getPathname());

        return \str_replace('\\', '/', $directory . '/' . \trim($this->target, '/'));
    }

    /**
     * @return non-empty-string
     */
    public function render(): string
    {
        return \sprintf("%include('%s')", $this->getTargetPathname());
    }
}
