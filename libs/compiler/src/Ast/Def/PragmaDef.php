<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PragmaDef extends Definition
{
    /**
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    public function __construct(
        public string $name,
        public string $value,
    ) {
        assert($name !== '', 'Pragma name must not be empty');
    }
}
