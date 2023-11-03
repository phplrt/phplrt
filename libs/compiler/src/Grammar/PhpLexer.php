<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Source\File;
use Phplrt\Lexer\Token\Token;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;

class PhpLexer implements LexerInterface
{
    private bool $inline;

    public function __construct(bool $inline = true)
    {
        $this->inline = $inline;
    }

    /**
     * @param resource|string|ReadableInterface $source
     * @param int<0, max> $offset
     * @return iterable<TokenInterface>
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function lex($source, int $offset = 0): iterable
    {
        $tokens = \token_get_all($this->read(File::new($source), $offset));

        foreach ($tokens as $i => $token) {
            if ($this->inline && $i === 0) {
                continue;
            }

            if (\is_array($token)) {
                yield new Token($this->getName($token[0]), $token[1], $offset);

                $offset += \strlen($token[1]);

                continue;
            }

            yield new Token($this->getName($token), $token, $offset);

            $offset += \strlen($token);
        }

        yield new EndOfInput($offset);
    }

    private function read(ReadableInterface $readable, int $offset): string
    {
        $source = $readable->getContents();

        $prefix = $this->inline ? '<?php ' : '';

        return $prefix . ($offset === 0 ? $source : \substr($source, $offset));
    }

    /**
     * @param int|string $id
     */
    private function getName($id): string
    {
        if (\is_string($id)) {
            return $id;
        }

        return \token_name($id);
    }
}
