<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\StackTrace\Trace;
use Phplrt\Position\Position;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\Source\FileInterface;
use Phplrt\Source\ReadableInterface;
use Phplrt\StackTrace\Record\NodeRecord;
use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\StackTrace\Record\TokenRecord;
use Phplrt\StackTrace\Record\RecordInterface;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Class GrammarException
 */
class GrammarException extends \LogicException
{
    /**
     * @var string
     */
    private const ERROR_HEADER = '%s: %s in %s:%d';

    public $trace;

    /**
     * GrammarException constructor.
     *
     * @param string $message
     * @param ReadableInterface $source
     * @param int $offset
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function __construct(string $message, ReadableInterface $source, int $offset)
    {
        parent::__construct($message);

        $this->file = $source instanceof FileInterface ? $source->getPathname() : '{source}';
        $this->line = Position::fromOffset($source, $offset)->getLine();
    }
}
