<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Lexer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\PCRE\Compiler;
use Phplrt\Lexer\Token\Channel;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Lexer\Token\Token;
use Phplrt\Source\Exception\SourceExceptionInterface;
use Phplrt\Source\File;

final class Lexer implements LexerInterface
{
    /**
     * @var string|null
     */
    private ?string $pattern = null;

    /**
     * @var array<int, string>
     */
    private readonly array $tokens;

    /**
     * @var array<string, string|int>
     */
    private readonly array $mappings;

    /**
     * @param LexerCreateInfo $info
     */
    public function __construct(
        private readonly LexerCreateInfo $info
    ) {
        $this->bootTokens();
    }

    /**
     * @return void
     */
    private function bootTokens(): void
    {
        $tokens = $mappings = [];
        $identifier = 0;

        foreach ($this->info->tokens as $token => $pcre) {
            $mappings[$alias = 'T' . $identifier++] = $token;
            $tokens[$alias] = $pcre;
        }

        [$this->tokens, $this->mappings] = [$tokens, $mappings];
    }

    /**
     * {@inheritDoc}
     *
     * @param positive-int|0 $offset
     * @throws SourceExceptionInterface
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function lex(mixed $source, int $offset = 0): iterable
    {
        /** @psalm-suppress MixedArgument */
        $source = File::new($source);

        foreach ($this->execute($source, $offset) as $token) {
            $channel = $token->getChannel();

            // Checking that the channel should throw exceptions
            if (\in_array($channel, $this->info->throw, true)) {
                throw UnrecognizedTokenException::fromToken($source, $token);
            }

            yield $token;
        }
    }

    /**
     * @param ReadableInterface $source
     * @param int $offset
     * @return \Traversable<TokenInterface>
     */
    private function execute(ReadableInterface $source, int $offset): \Traversable
    {
        $result = $this->match($source->getContents(), $offset);
        $error = null;

        /** @var array<string> $payload */
        foreach ($result as $payload) {
            $name = \array_pop($payload);
            $name = $this->mappings[$name] ?? $name;

            $channel = $this->info->channels[$name] ?? Channel::DEFAULT;

            /**
             * Capture offset
             * @var positive-int|0 $previous
             */
            $previous = $offset;
            $offset += \strlen($payload[0]);

            // Capture error token
            if ($name === $this->info->unknownTokenName) {
                $error ??= Token::unknown(
                    value: '',
                    offset: $previous,
                    name: $this->info->unknownTokenName,
                    channel: $this->info->channels[$name] ?? Channel::UNKNOWN
                );

                /** @psalm-suppress InaccessibleProperty */
                $error->value .= $payload[0];
                continue;
            }

            // Transition to a known token from error token
            if ($error !== null) {
                yield $error;
                $error = null;
                continue;
            }

            yield \count($payload) > 1
                ? new Composite($name, \array_shift($payload), $previous, $channel, $payload)
                : new Token($name, $payload[0], $previous, $channel);
        }

        yield Token::eoi(
            offset: $offset,
            name: $this->info->eoiTokenName,
            channel: $this->info->channels[$this->info->eoiTokenName] ?? Channel::END_OF_INPUT
        );
    }

    /**
     * @param string $source
     * @param positive-int|0 $offset
     * @return array<positive-int|0|"MARK", string>
     */
    private function match(string $source, int $offset): array
    {
        if ($this->pattern === null) {
            $compiler = new Compiler($this->info);

            $this->pattern = $compiler->compile($this->tokens, $this->mappings);
        }

        \preg_match_all($this->pattern, $source, $matches, \PREG_SET_ORDER, $offset);

        return $matches;
    }
}