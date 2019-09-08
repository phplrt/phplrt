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
use Phplrt\Lexer\Driver\DriverInterface;
use Phplrt\Lexer\Driver\Markers;
use Phplrt\Lexer\Exception\UnrecognizedTokenException;
use Phplrt\Lexer\Token\EndOfInput;
use Phplrt\Lexer\Token\Token;

/**
 * Class Lexer
 */
class Lexer implements LexerInterface
{
    /**
     * @var array|string[]
     */
    protected $tokens;

    /**
     * @var array|string[]
     */
    protected $skip;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * Lexer constructor.
     *
     * @param array|string[] $tokens
     * @param array|string[] $skip
     */
    public function __construct(array $tokens, array $skip = [])
    {
        $this->tokens = $tokens;
        $this->skip   = $skip;
    }

    /**
     * @param string ...$names
     * @return Lexer|$this
     */
    public function skip(string ...$names): self
    {
        $this->skip = \array_merge($this->skip, $names);

        return $this;
    }

    /**
     * @param string $token
     * @param string $pattern
     * @return Lexer|$this
     */
    public function add(string $token, string $pattern): self
    {
        $this->reset();
        $this->tokens[$token] = $pattern;

        return $this;
    }

    /**
     * @return void
     */
    private function reset(): void
    {
        $this->driver = null;
    }

    /**
     * @param string $tokens
     * @return Lexer|$this
     */
    public function addMany(string $tokens): self
    {
        $this->reset();
        $this->tokens = \array_merge($this->tokens, $tokens);

        return $this;
    }

    /**
     * @param resource|string $source
     * @param int $offset
     * @return iterable
     */
    public function lex($source, int $offset = 0): iterable
    {
        $driver  = $this->driver ?? $this->driver  = $this->getDriver();
        $unknown = [];

        foreach ($driver->lex($source, $offset) as $token) {
            if (\in_array($token->getName(), $this->skip, true)) {
                continue;
            }

            if ($token->getName() === $driver::UNKNOWN_TOKEN_NAME) {
                $unknown[] = $token;
                continue;
            }

            if (\count($unknown) && $token->getName() !== $driver::UNKNOWN_TOKEN_NAME) {
                throw new UnrecognizedTokenException($this->reduce($unknown));
            }

            yield $token;
        }

        if (\count($unknown)) {
            throw new UnrecognizedTokenException($this->reduce($unknown));
        }

        yield new EndOfInput(isset($token) ? $token->getOffset() + $token->getBytes() : 0);
    }

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface
    {
        return new Markers($this->tokens, false);
    }

    /**
     * @param array|TokenInterface[] $tokens
     * @return TokenInterface
     */
    private function reduce(array $tokens): TokenInterface
    {
        $value = (string)\array_reduce($tokens, static function (string $data, TokenInterface $token): string {
            return $data . $token->getValue();
        }, '');

        return new Token(\reset($tokens)->getName(), $value, \reset($tokens)->getOffset());
    }
}
