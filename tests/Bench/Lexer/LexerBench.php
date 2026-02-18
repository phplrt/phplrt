<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Lexer;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\File;

abstract readonly class LexerBench
{
    protected ReadableInterface $source;

    public function prepare(): void
    {
        $this->source = File::fromString(<<<'PHP'
            array{
                initial: array-key,
                tokens: array{
                    default: array<non-empty-string, non-empty-string>,
                    ...
                },
                some: "hello world hello world hello world hello world",
                skip: list<non-empty-string>,
                grammar: array<array-key, \Phplrt\Parser\Grammar\RuleInterface>,
                reducers: array<array-key, callable(\Phplrt\Parser\Context, mixed):mixed>,
                transitions?: array<array-key, mixed>
            }
            PHP);
    }
}
