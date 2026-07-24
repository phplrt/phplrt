<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Internal\Executor;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\LexerInterface;

/**
 * Adapts a foreign lexer to the executor contract, which allows embedding any
 * {@see LexerInterface} implementation into the grammar as a state.
 *
 * Such a lexer decides on its own where its fragment ends, so it is expected
 * to stop reading by itself.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Lexer
 */
final readonly class DelegatingExecutor implements ExecutorInterface
{
    public function __construct(
        private LexerInterface $lexer,
    ) {}

    public function run(string $source, int $offset, array &$tokens): int
    {
        foreach ($this->lexer->lex($source, $offset) as $token) {
            /**
             * The terminal token only marks the end of the embedded lexer's
             * own fragment, so it must not leak into the resulting stream.
             */
            if ($token->channel === Channel::EndOfInput) {
                break;
            }

            $tokens[] = $token;
            $offset = $token->offset + \strlen($token->value);
        }

        return $offset;
    }
}
