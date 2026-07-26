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
use Phplrt\Parser\Internal\RecursiveDescentTracer;
use Phplrt\Parser\Internal\TraceReducer;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

/**
 * The lookahead and the kept rules are optimizations: Omitting them turns the
 * parsing into a plain PEG recognition, which is slower and reports the syntax
 * errors at a later position, but yields the same result.
 *
 * @phpstan-import-type ReducerType from TraceReducer
 */
final readonly class Parser implements ParserInterface
{
    /**
     * The identifiers of the tokens a rule may begin with, indexed by the rule
     * identifiers. Empty in case the lookahead is unknown.
     *
     * @var array<int, array<int, true>>
     */
    private array $first;

    /**
     * The rules that may be recognized without consuming a token, indexed by
     * the rule identifiers. Every rule is nullable in case the lookahead is
     * unknown, so no rule is cut off.
     *
     * @var array<int, bool>
     */
    private array $nullable;

    /**
     * The rules that are present in the resulting tree, indexed by the rule
     * identifiers. Every rule is present in case they are unknown.
     *
     * @var array<int, bool>
     */
    private array $kept;

    private TraceReducer $reducer;

    /**
     * @param array<int, array<int, true>> $first
     * @param array<int, bool> $nullable
     * @param array<int, bool> $kept
     * @param array<int<0, max>, ReducerType> $reducers
     */
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
        array $first = [],
        array $nullable = [],
        array $kept = [],
        array $reducers = [],
        /**
         * Selects which tokens are passed to the grammar.
         */
        private FilterInterface $filter = new ChannelFilter(),
    ) {
        $containsLookaheadTable = $first !== [] && $nullable !== [];

        $this->first = $containsLookaheadTable ? $first : [];
        $this->nullable = $containsLookaheadTable ? $nullable : self::admitEveryRule($grammar);
        $this->kept = $kept === [] ? self::admitEveryRule($grammar) : $kept;

        $this->reducer = new TraceReducer(
            grammar: $grammar,
            reducers: $reducers,
            rule: $initial,
        );
    }

    /**
     * Fills a table admitting every rule of the grammar, so the recognition
     * reads it the same way as a known one.
     *
     * @param list<RuleInterface> $grammar
     * @return array<int, true>
     */
    private static function admitEveryRule(array $grammar): array
    {
        return \array_fill_keys(\array_keys($grammar), true);
    }

    private function trace(BufferInterface $buffer): Success|Failure
    {
        return RecursiveDescentTracer::trace(
            grammar: $this->grammar,
            initial: $this->initial,
            buffer: $buffer,
            first: $this->first,
            nullable: $this->nullable,
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

        $reducer = $this->reducer->createContextualReducer($source);

        return $reducer->reduce($result);
    }

    private function lex(string $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        $filtered = $this->filter->apply($stream);

        return new ArrayBuffer($filtered);
    }
}
