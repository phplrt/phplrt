<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Parser\Exception\ParserRuntimeExceptionInterface;

/**
 * Class ParserRuntimeException
 */
class ParserRuntimeException extends ParserException implements ParserRuntimeExceptionInterface
{
    /**
     * @var string
     */
    public const ERROR_UNEXPECTED_TOKEN = 'Syntax error, unexpected %s';

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * LexerRuntimeException constructor.
     *
     * @param string $message
     * @param TokenInterface $token
     * @param NodeInterface $node
     * @param \Throwable|null $prev
     */
    public function __construct(string $message, TokenInterface $token, NodeInterface $node = null, \Throwable $prev = null)
    {
        $this->token = $token;
        $this->node = $node ?? $this->createNode($token);

        parent::__construct($message, 0, $prev);
    }

    /**
     * @param TokenInterface $token
     * @return NodeInterface
     */
    private function createNode(TokenInterface $token): NodeInterface
    {
        return new class ($token->getOffset()) implements NodeInterface {
            /**
             * @var int
             */
            private $offset;

            /**
             * @param int $offset
             */
            public function __construct(int $offset)
            {
                $this->offset = $offset;
            }

            /**
             * @return int
             */
            public function getOffset(): int
            {
                return $this->offset;
            }

            /**
             * @return \Traversable|NodeInterface[]
             */
            public function getIterator(): \Traversable
            {
                return new \EmptyIterator();
            }
        };
    }

    /**
     * @return NodeInterface
     */
    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        return $this->token;
    }
}
