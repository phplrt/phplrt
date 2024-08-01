<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TokenDef extends Definition
{
    public string $name;

    public string $value;

    public bool $keep;

    public ?string $state = null;

    public ?string $next = null;

    public function __construct(string $name, string $value, bool $keep = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->keep = $keep;
    }
}
