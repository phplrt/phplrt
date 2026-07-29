<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Exception\LexerExceptionInterface;
use Phplrt\Contracts\Lexer\Exception\RuntimeExceptionInterface as LexerRuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Parser\Analysis\Diagnostic;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\FailureResult;
use Phplrt\Parser\Analysis\Result\PartialResult;
use Phplrt\Parser\Analysis\Result\Result;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
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
     * Describes what has kept the grammar from reading the source any further.
     *
     * @param TokenInterface $stoppedAt the token to describe in case of the
     *        grammar has broken nowhere in particular
     */
    private function describe(
        ReadableInterface $source,
        Failure $failure,
        TokenInterface $stoppedAt,
    ): UnexpectedTokenException {
        // A grammar reads much further than it keeps, so what is wrong is
        // where the reading has broken rather than where it has stopped: a
        // source ending in the middle of a rule stops where that rule begins,
        // and breaks at the end of the input
        $token = $failure->token ?? $stoppedAt;

        return UnexpectedTokenException::fromToken($source, $token, $failure->expected);
    }

    /**
     * Reads as much of the source as the grammar describes and reports what it
     * has made of it.
     *
     * Nothing about the source is an error: how far the grammar goes is told
     * by the class of the result, and what stands in the way by its
     * diagnostics.
     *
     * @return ($mode is Mode::Tolerant ? Result : Result<null>)
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function analyze(ReadableInterface $source, Mode $mode = Mode::Tolerant): Result
    {
        $buffer = $this->lex($source);
        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            $error = $this->describe($source, $result, $buffer->current);

            return new FailureResult($error->token, [new Diagnostic($error)]);
        }

        $value = $mode === Mode::Tolerant ? $this->reduce($source, $result) : null;

        if ($result->furthest === null) {
            return new SuccessfulResult($value);
        }

        return new PartialResult($value, $result->stoppedAt, [
            new Diagnostic($this->describe($source, $result->furthest, $result->stoppedAt)),
        ]);
    }

    /**
     * Parses the source into an AST.
     *
     * A source the grammar does not describe in full is an error rather than
     * a result.
     *
     * @throws UnexpectedTokenException on a syntax error
     * @throws ParserSourceException in case of the source cannot be read
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    public function parse(ReadableInterface $source): mixed
    {
        $buffer = $this->lex($source);
        $result = $this->trace($buffer);

        if ($result instanceof Failure) {
            throw $this->describe($source, $result, $buffer->current);
        }

        if ($result->furthest !== null) {
            throw $this->describe($source, $result->furthest, $result->stoppedAt);
        }

        return $this->reduce($source, $result);
    }

    /**
     * @throws ParserSourceException in case of the source cannot be read
     */
    private function reduce(ReadableInterface $source, Success $result): mixed
    {
        try {
            $content = $source->content;
        } catch (SourceExceptionInterface $e) {
            throw ParserSourceException::becauseSourceIsNotReadable($e);
        }

        return $this->reducer->createContext($source, $content)
            ->reduce($result);
    }

    /**
     * @throws LexerExceptionInterface in case of the source cannot be read into
     *         tokens
     * @throws LexerRuntimeExceptionInterface in case of the source contains
     *         what no token recognizes
     */
    private function lex(ReadableInterface $source): BufferInterface
    {
        $stream = $this->lexer->lex($source);

        $filtered = $this->filter->apply($stream);

        return new ArrayBuffer($filtered);
    }
}
