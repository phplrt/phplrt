<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Executor;

use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\LexerCreateInfo;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;

final readonly class MarkersExecutor implements LexerInterface
{
    private Token $prototype;

    public array $transitions;

    public function __construct(
        private LexerCreateInfo $config,
        /**
         * @var array<int, ChannelInterface>
         */
        private array $channels = [],
    ) {
        $this->transitions = $config->transitions;
        $this->prototype = new Token();
    }

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        $content = $source->content;

        \preg_match_all($this->config->pattern, $content, $matches, 0, $offset);

        // PHP stack optimization:
        // Dereference found variables
        /** @var list<string> $foundValues */
        $foundValues = $matches[0];
        /** @var list<non-empty-string> $foundNames */
        $foundNames = $matches['MARK'] ?? [];

        // PHP stack optimization:
        // Import HOT variables from object props
        $names = $this->config->names;
        $channels = $this->channels;
        $token = $this->prototype;

        $result = [];
        foreach ($foundNames as $index => $alias) {
            $bytes = \strlen($value = $foundValues[$index]);

            $token->id = $id = (int) $alias;
            $token->name = $names[$id] ?? null;
            $token->offset = $offset;
            $token->value = $value;
            $token->channel = $channels[$id] ?? null;

            // object clone is faster than instantiation
            $result[] = $token = clone $token;

            $offset += $bytes;
        }

        $result[] = new EndOfInput($offset);

        /** @var iterable<array-key, TokenInterface> */
        return $result;
    }
}
