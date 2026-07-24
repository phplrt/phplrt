<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Buffer\ArrayBuffer;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Filter\ChannelFilter;
use Phplrt\Parser\Filter\FilterInterface;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Recognizer;

final readonly class Parser implements ParserInterface
{
    public function __construct(
        private LexerInterface $lexer,
        /**
         * A map of rule identifier and its inert definition.
         *
         * @var array<int, RuleInterface>
         */
        private array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         */
        private int $initial,
        /**
         * Selects which tokens of the lexed stream reach the buffer.
         */
        private FilterInterface $filter = new ChannelFilter(),
    ) {}

    /**
     * Checks that the given source fully matches the grammar.
     *
     * This is a recognition-only pass: nothing is materialized, so it is the
     * cheapest way to answer whether the source is syntactically valid.
     */
    public function check(string $source): bool
    {
        $buffer = $this->lex($source);

        $recognizer = new Recognizer($this->grammar, $buffer);

        $result = $recognizer->recognize($this->initial);

        if ($result === false) {
            return false;
        }

        return $this->isEndOfInput($buffer->current());
    }

    /**
     * Runs the recognition pass and reports the first place the source stops
     * matching the grammar.
     *
     * The reduction pass that turns the recognized rules into an AST is built
     * on top of this one and is not part of this runtime slice yet, so a
     * successful analysis currently yields no nodes.
     *
     * @return iterable<array-key, object>
     */
    public function parse(string $source): iterable
    {
        $buffer = $this->lex($source);

        $recognizer = new Recognizer($this->grammar, $buffer);

        if (!$recognizer->recognize($this->initial)
            || !$this->isEndOfInput($buffer->current())
        ) {
            throw UnexpectedTokenException::fromToken(
                $recognizer->getFurthestToken() ?? $buffer->current(),
            );
        }

        return [];
    }

    private function lex(string $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        $filtered = $this->filter->apply($stream);

        return new ArrayBuffer($filtered);
    }

    private function isEndOfInput(TokenInterface $token): bool
    {
        return $token->channel === Channel::EndOfInput;
    }
}
