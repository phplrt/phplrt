<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Grammar\RuleInterface;

final readonly class Parser implements ParserInterface
{
    public function __construct(
        private LexerInterface $lexer,
        /** @var array<int, RuleInterface> */
        private array $grammar,
        private int $initial,
    ) {}

    private function createBuffer(string $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        return new ArrayBuffer($stream);
    }

    public function parse(string $source): iterable
    {
        $buffer = $this->createBuffer($source);

        return [];
    }
}
