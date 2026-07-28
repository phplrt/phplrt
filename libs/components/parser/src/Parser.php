<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Exception\ParserSourceException;
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
 * @phpstan-import-type ReducerType from TraceReducer
 */
readonly class Parser implements ParserInterface
{
    /**
     * The identifiers of the tokens a rule may begin with, indexed by the rule
     * identifiers.
     *
     * @var array<int, array<int, true>>
     */
    private array $startTokens;

    /**
     * The rules that may be recognized without consuming a token, indexed by
     * the rule identifiers.
     *
     * @var array<int, bool>
     */
    private array $matchesEmptyInput;

    /**
     * The rules that become a node of the result, indexed by the rule
     * identifiers.
     *
     * A rule that is present builds its own value from the values of its
     * children and passes it to its reducer. A rule that is absent gives the
     * values of its children to the rule above, so it leaves nothing in the
     * result.
     *
     * @var array<int, bool>
     */
    private array $presentInTree;

    private TraceReducer $reducer;

    /**
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @param array<int, bool> $presentInTree
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
        array $reducers = [],
        array $startTokens = [],
        array $matchesEmptyInput = [],
        array $presentInTree = [],
        /**
         * Selects which tokens are passed to the grammar.
         */
        private FilterInterface $filter = new ChannelFilter(),
    ) {
        // Both tables are needed to skip a rule, so one without the other is
        // useless
        $containsLookaheadTable = $startTokens !== [] && $matchesEmptyInput !== [];

        // If this fields is empty, the parser will turn into a regular PEG,
        // which will slow down the parsing.
        //
        // Furthermore, errors will be reported at later stages (in more nested
        // rules), since the dataset from these two tables doesn't allow for
        // reproducing them at earlier stages.
        $this->startTokens = $containsLookaheadTable ? $startTokens : [];
        $this->matchesEmptyInput = $containsLookaheadTable ? $matchesEmptyInput : self::admitEveryRule($grammar);

        // If a rule doesn't change anything, it can be skipped. This reduces
        // the amount of tracing and speeds up subsequent processing, although
        // the computational result will remain the same.
        //
        // If the set of rules isn't explicitly passed, then we simply fill them
        // all in, assuming every rule is important.
        $this->presentInTree = $presentInTree === [] ? self::admitEveryRule($grammar) : $presentInTree;

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
            startTokens: $this->startTokens,
            matchesEmptyInput: $this->matchesEmptyInput,
            presentInTree: $this->presentInTree,
        );
    }

    /**
     * Checks whether the source is syntactically valid against the grammar.
     */
    public function check(ReadableInterface $source): bool
    {
        $buffer = $this->lex($source);

        return $this->trace($buffer) instanceof Success;
    }

    /**
     * Parses the source into an AST.
     *
     * @throws UnexpectedTokenException on a syntax error
     */
    public function parse(ReadableInterface $source): mixed
    {
        $buffer = $this->lex($source);

        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            throw UnexpectedTokenException::fromToken($source, $result->token ?? $buffer->current);
        }

        try {
            $content = $source->content;
        } catch (SourceExceptionInterface $e) {
            throw ParserSourceException::becauseSourceIsNotReadable($e);
        }

        return $this->reducer->createContext($source, $content)
            ->reduce($result);
    }

    private function lex(ReadableInterface $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        $filtered = $this->filter->apply($stream);

        return new ArrayBuffer($filtered);
    }
}
