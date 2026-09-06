<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * The place the generated code is written into.
 *
 * Nothing of this changes what the parser recognizes: it only says how the code
 * is spelled, so that it fits the application it is generated for.
 */
final class OutputContext
{
    /**
     * The symbols the generated code loads before it refers to them, in the
     * order their declarations depend on each other.
     *
     * @var list<SymbolInclude>
     */
    public array $includes = [];

    /**
     * Provides PHP target version
     */
    public readonly TargetPhpVersion $php;

    public function __construct(
        /**
         * The namespace the generated code belongs to, or {@see null} in case
         * of the code belongs to the global one.
         *
         * @var non-empty-string|null
         */
        public readonly ?string $namespace = null,
        /**
         * The classes the generated code refers to by their short names.
         *
         * @var list<ClassImport>
         */
        public readonly array $imports = [],
        /**
         * The name of the class the parser is declared as, or {@see null} in
         * case of the parser is named by nothing and is returned by the file
         * it is written into.
         *
         * @var non-empty-string|null
         */
        public readonly ?string $class = null,
        /**
         * Provides PHP target version
         */
        ?TargetPhpVersion $php = null,
        /**
         * Whether the parser is written down as readonly.
         */
        public readonly bool $readonly = true,
        /**
         * The way the class of the parser is declared.
         */
        public readonly ClassModifier $modifier = ClassModifier::Default,
    ) {
        $this->php = $php
            ?? TargetPhpVersion::current();
    }
}
