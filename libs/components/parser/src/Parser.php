<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Source\SourceFactory;

final readonly class Parser implements ParserInterface
{
    private SourceFactoryInterface $sources;

    private \SplStack $stack;

    public function __construct(
        private LexerInterface $lexer,
        private array $grammar,
        private int $initial,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();
        $this->stack = new \SplStack();
    }

    public function parse(mixed $source): iterable
    {
        $readable = $this->sources->create($source);

        $buffer = new ArrayBuffer($this->lexer->lex($readable));

        $rule = $this->grammar[$this->initial];

        return $this->stack;
    }
}
