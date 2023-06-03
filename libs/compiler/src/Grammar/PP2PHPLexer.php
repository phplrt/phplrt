<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Source\Exception\NotAccessibleException;

class PP2PHPLexer implements LexerInterface
{
    /**
     * @var PhpLexer
     */
    private PhpLexer $lexer;

    /**
     * @var int<0, max>
     */
    private int $depth = 0;

    /**
     * @param PhpLexer $lexer
     */
    public function __construct(PhpLexer $lexer)
    {
        $this->lexer = $lexer;
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
        $this->depth = 0;

        $children = [];
        $value  = '';

        foreach ($this->lexer->lex($source, $offset) as $inner) {
            if ($inner->getName() === '{') {
                ++$this->depth;
            }

            if ($inner->getName() === '}') {
                /** @psalm-suppress PossiblyInvalidPropertyAssignmentValue */
                --$this->depth;
            }

            if ($this->depth < 0) {
                break;
            }

            $children[] = $inner;
            $value .= $inner->getValue();
        }

        yield new Composite('T_PHP_CODE', $value, $offset, $children);
    }
}
