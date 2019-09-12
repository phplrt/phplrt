<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Exception;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;

/**
 * Class ParserRuntimeException
 */
class ParserRuntimeException extends ParserException implements RuntimeExceptionInterface
{
    /**
     * @var string
     */
    public const ERROR_UNEXPECTED_TOKEN = 'Syntax error, unexpected %s';

    /**
     * @var string
     */
    public const ERROR_UNRECOGNIZED_TOKEN = 'Syntax error, unrecognized lexeme %s';

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * LexerRuntimeException constructor.
     *
     * @param string $message
     * @param NodeInterface $node
     * @param \Throwable|null $prev
     */
    public function __construct(string $message, NodeInterface $node, \Throwable $prev = null)
    {
        $this->node = $node;

        parent::__construct($message, 0, $prev);
    }

    /**
     * @return NodeInterface
     */
    public function getNode(): NodeInterface
    {
        return $this->node;
    }
}
