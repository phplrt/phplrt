<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Lexer\Token\Renderer;
use Phplrt\StackTrace\Record\NodeRecord;
use Phplrt\StackTrace\Record\RecordInterface;
use Phplrt\StackTrace\Record\TokenRecord;
use Phplrt\StackTrace\Trace;

/**
 * Class GrammarException
 */
class GrammarException extends \LogicException
{
    /**
     * @var string
     */
    private const ERROR_HEADER = '%s: %s in %s:%d';

    /**
     * @var Trace
     */
    public $trace;

    /**
     * @return string
     */
    public function __toString(): string
    {
        $result = [];

        $result[] = \vsprintf(self::ERROR_HEADER, [
            static::class,
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
        ]);

        if ($this->trace && ! $this->trace->isEmpty()) {
            $result[] = 'Grammar Stack Trace:';
            $result[] = $this->trace->render(static function (RecordInterface $item) {
                switch (true) {
                    case $item instanceof NodeRecord:
                        $node = $item->node;

                        return $node instanceof Expression ? $node->render() : '{internal}';

                    case $item instanceof TokenRecord:
                        return '{ ' . $item->token->getName() . ': ' . (new Renderer())->value($item->token) . ' }';

                    default:
                        return '{main}';
                }
            });
        }

        $result[] = 'Stack Trace:';
        $result[] = $this->getTraceAsString();

        return \implode("\n", $result);
    }
}
