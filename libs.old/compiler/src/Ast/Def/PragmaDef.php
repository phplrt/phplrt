<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal this is an internal class, please do not use it in your application code
 * @psalm-internal Phplrt\Compiler
 */
class PragmaDef extends Definition
{
    public function __construct(
        /**
         * @var non-empty-string
         */
        public string $name,
        /**
         * @var non-empty-string
         */
        public string $value,
    ) {
        assert($name !== '', 'Pragma name must not be empty');
        assert($name !== '', 'Name must not be empty');
    }
}
