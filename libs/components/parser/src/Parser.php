<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Filter\ChannelFilter;
use Phplrt\Parser\Internal\Filter\FilterInterface;
use Phplrt\Parser\Internal\ParseTree\Lookahead;
use Phplrt\Parser\Internal\RecursiveDescentTracer;
use Phplrt\Parser\Internal\ParseTree\KeptConstruction;
use Phplrt\Parser\Internal\ParseTree\LookaheadConstruction;
use Phplrt\Parser\Internal\TraceReducer;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * @phpstan-import-type ReducerType from TraceReducer
 */
final readonly class Parser implements ParserInterface
{
    private TraceReducer $reducer;

    private Lookahead $lookahead;

    /**
     * @var array<int, bool>
     */
    private array $kept;

    public function __construct(
        private LexerInterface $lexer,
        /**
         * @var list<RuleInterface>
         */
        private array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         *
         * @var int<0, max>
         */
        private int $initial,
        /**
         * @var array<int<0, max>, ReducerType>
         */
        private array $reducers = [],
        /**
         * Selects which tokens are passed to the grammar.
         */
        private FilterInterface $filter = new ChannelFilter(),
    ) {
        $this->reducer = new TraceReducer($this->grammar, $this->reducers);

        $this->lookahead = LookaheadConstruction::compute($this->grammar);
        $this->kept = KeptConstruction::compute($this->grammar, $this->reducers);
    }

    private function trace(BufferInterface $buffer): Success|Failure
    {
        return RecursiveDescentTracer::trace(
            grammar: $this->grammar,
            initial: $this->initial,
            buffer: $buffer,
            lookahead: $this->lookahead,
            kept: $this->kept,
        );
    }

    /**
     * Checks whether the source is syntactically valid against the grammar.
     */
    public function check(string $source): bool
    {
        $buffer = $this->lex($source);

        return $this->trace($buffer) instanceof Success;
    }

    /**
     * Parses the source into an AST.
     *
     * @throws UnexpectedTokenException on a syntax error
     */
    public function parse(string $source): mixed
    {
        $buffer = $this->lex($source);

        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            throw UnexpectedTokenException::fromToken($result->token ?? $buffer->current, $source);
        }

        return $this->reducer->reduce($result, $source);
    }

    private function lex(string $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        $filtered = $this->filter->apply($stream);

        return new ArrayBuffer($filtered);
    }
}
