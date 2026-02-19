<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Executor;

use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\LexerCreateInfo;
use Phplrt\Lexer\Token\EndOfInputToken;
use Phplrt\Lexer\Token\Token;

final readonly class MarkersExecutor implements LexerInterface
{
    public array $transitions;

    public function __construct(
        private LexerCreateInfo $config,
        /**
         * @var array<int, ChannelInterface>
         */
        private array $channels = [],
    ) {
        $this->transitions = $config->transitions;
    }

    public function lex(ReadableInterface $source, int $offset = 0): iterable
    {
        /** @phpstan-ignore-next-line : JIT range tuning */
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        $content = $source->content;

        \preg_match_all($this->config->pattern, $content, $matches, 0, $offset);

        if (!isset($matches['MARK'])) {
            return [new EndOfInputToken($source, $offset)];
        }

        // PHP stack optimization:
        // Dereference found variables
        /** @var list<string> $foundValues */
        $foundValues = $matches[0];
        /** @var list<non-empty-string> $foundNames */
        $foundNames = $matches['MARK'];

        // PHP stack optimization:
        // Import "hot" variables from object properties, which will
        // reduce the number of hops to access the memory address.
        $names = $this->config->names;
        $channels = $this->channels;

        $prototype = new Token(
            id: -1,
            name: null,
            channel: Channel::DEFAULT,
            source: $source,
            value: '',
            offset: $offset,
        );

        $index = 0;
        $result = \array_fill(0, \count($foundNames) + 1, null);

        /** @phpstan-ignore-next-line : Allow "$index" overwrite */
        foreach ($foundNames as $index => $alias) {
            // Clone optimization: speeds up the creation
            // of a new object (faster than instantiation)
            $token = clone $prototype;

            $id = (int) $alias;
            $name = null;
            $value = $foundValues[$index];
            $length = \strlen($value);

            if (isset($names[$id])) {
                $name = $names[$id];
            }

            $token->id = $id;                       // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->name = $name;                   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->offset = $offset;               // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            $token->value = $value;                 // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass

            if (isset($channels[$id])) {
                $token->channel = $channels[$id];   // @phpstan-ignore property.readOnlyByPhpDocAssignOutOfClass
            }

            $result[$index] = $token;
            $offset += $length;
        }

        $result[$index + 1] = new EndOfInputToken($source, $offset);

        /** @phpstan-ignore-next-line : Returns valid type */
        return $result;
    }
}
