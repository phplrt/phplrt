<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

/**
 * A class the generated code refers to by its short name.
 */
final readonly class ClassImport implements \Stringable
{
    public function __construct(
        /**
         * The fully qualified name of the class.
         *
         * @var non-empty-string
         */
        public string $class,
        /**
         * The name the class is referred to by, or {@see null} in case of the
         * class is referred to by the last part of its own name.
         *
         * @var non-empty-string|null
         */
        public ?string $alias = null,
    ) {}

    public function __toString(): string
    {
        if ($this->alias === null) {
            return $this->class;
        }

        return $this->class . ' as ' . $this->alias;
    }
}
