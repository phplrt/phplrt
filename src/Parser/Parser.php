<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser;

use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Parser\Buffer\BufferInterface;
use Phplrt\Parser\Buffer\EagerBuffer;
use Phplrt\Parser\Builder\BuilderInterface;
use Phplrt\Parser\Builder\Common;
use Phplrt\Parser\Exception\ParserRuntimeException;

/**
 * Class Parser
 */
class Parser extends AbstractParser
{
    use Facade;

    /**
     * {@inheritDoc}
     */
    public function builder(): BuilderInterface
    {
        return new Common();
    }

    /**
     * {@inheritDoc}
     */
    public function buffer(\Generator $stream, int $size): BufferInterface
    {
        return new EagerBuffer($stream);
    }

    /**
     * {@inheritDoc}
     */
    public function read($source)
    {
        return $source;
    }

    /**
     * {@inheritDoc}
     */
    public function onLexerError(TokenInterface $token): \Throwable
    {
        $message = \vsprintf(ParserRuntimeException::ERROR_UNRECOGNIZED_TOKEN, [
            (new Renderer())->render($token),
        ]);

        return new ParserRuntimeException($message);
    }

    /**
     * {@inheritDoc}
     */
    public function getInitialRule(BufferInterface $buffer, array $rules)
    {
        return \count($rules) ? \array_key_first($rules) : 0;
    }

    /**
     * {@inheritDoc}
     */
    public function onSyntaxError(TokenInterface $token): \Throwable
    {
        $message = \vsprintf(ParserRuntimeException::ERROR_UNEXPECTED_TOKEN, [
            (new Renderer())->render($token),
        ]);

        return new ParserRuntimeException($message);
    }
}
