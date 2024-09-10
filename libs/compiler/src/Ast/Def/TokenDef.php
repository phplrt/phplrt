<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class TokenDef extends Definition
{
    public ?string $state = null;

    public ?string $next = null;

    public function __construct(
        public string $name,
        public string $value,
        public bool $keep = true,
    ) {}
}
