<?php

declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\UnexpectedTokenException;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Parser\Internal\Buffer\ArrayBuffer;
use Phplrt\Parser\Internal\Buffer\BufferInterface;
use Phplrt\Parser\Internal\Filter\ChannelFilter;
use Phplrt\Parser\Internal\Filter\FilterInterface;
use Phplrt\Parser\Internal\Recognizer;
use Phplrt\Parser\Internal\Tracing\Result\Failure;
use Phplrt\Parser\Internal\Tracing\Result\Success;

final readonly class Parser implements ParserInterface
{
    public function __construct(
        private LexerInterface $lexer,
        /**
         * @var array<int, RuleInterface>
         */
        private array $grammar,
        /**
         * The identifier of the rule the analysis starts at.
         */
        private int $initial,
        /**
         * Selects which tokens are passed to the grammar.
         */
        private FilterInterface $filter = new ChannelFilter(),
    ) {}

    /**
     * Checks whether the source is syntactically valid against the grammar.
     */
    public function check(string $source): bool
    {
        $buffer = $this->lex($source);

        $result = new Recognizer($this->grammar, $buffer)->recognize($this->initial);

        return $result instanceof Success
            && $this->isEndOfInput($buffer->current);
    }

    /**
     * Parses the source into an AST.
     *
     * @return object
     * @throws UnexpectedTokenException on a syntax error
     */
    public function parse(string $source): mixed
    {
        $buffer = $this->lex($source);

        $result = new Recognizer($this->grammar, $buffer)->recognize($this->initial);

        if ($result instanceof Failure) {
            throw UnexpectedTokenException::fromToken(
                $result->token ?? $buffer->current,
            );
        }

        if (!$this->isEndOfInput($buffer->current)) {
            throw UnexpectedTokenException::fromToken($buffer->current);
        }

        return $result;
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
