<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Factory\SourceFactoryInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Source\SourceFactory;

final readonly class Parser implements ParserInterface
{
    private SourceFactoryInterface $sources;

    public function __construct(
        private LexerInterface $lexer,
        /** @var array<int, RuleInterface> */
        private array $grammar,
        private int $initial,
        ?SourceFactoryInterface $sources = null,
    ) {
        $this->sources = $sources ?? SourceFactory::default();
    }

    public function parse(ReadableInterface $source): iterable
    {
        $readable = $this->sources->create($source);

        $buffer = new ArrayBuffer($this->filter($this->lexer->lex($readable)));

        return [];
    }
}
