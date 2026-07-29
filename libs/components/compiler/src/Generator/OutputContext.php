<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * The place the generated code is written into.
 *
 * Nothing of this changes what the parser recognizes: it only says how the code
 * is spelled, so that it fits the application it is generated for.
 */
final readonly class OutputContext
{
    public function __construct(
        /**
         * The namespace the generated code belongs to, or {@see null} in case
         * of the code belongs to the global one.
         *
         * @var non-empty-string|null
         */
        public ?string $namespace = null,
        /**
         * The classes the generated code refers to by their short names.
         *
         * @var list<ClassImport>
         */
        public array $imports = [],
        /**
         * The name of the class the parser is declared as, or {@see null} in
         * case of the parser is named by nothing and is returned by the file
         * it is written into.
         *
         * @var non-empty-string|null
         */
        public ?string $class = null,
    ) {}
}
