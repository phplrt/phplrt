<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Compiler\Ast\Expr\Expression;
use Phplrt\Compiler\Builder\IncludesVisitor;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Compiler\Grammar\GrammarInterface;
use Phplrt\Compiler\Grammar\PP2Grammar;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Position\Position;
use Phplrt\Visitor\Traverser;

/**
 * Class Builder
 */
class Builder
{
    /**
     * @var GrammarInterface
     */
    private $grammar;

    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * @var \SplObjectStorage
     */
    private $stack;

    /**
     * Compiler constructor.
     *
     * @param \SplFileInfo $file
     * @param GrammarInterface|null $grammar
     */
    public function __construct(\SplFileInfo $file, GrammarInterface $grammar = null)
    {
        $this->file    = $file;
        $this->grammar = $grammar ?? new PP2Grammar();
        $this->stack   = new \SplObjectStorage();
    }

    /**
     * @return iterable
     * @throws \Throwable
     */
    public function compile()
    {
        try {
            return $this->analyze($this->file);
        } catch (GrammarException $error) {
            $error->trace = $this->serializeTrace();

            throw $error;
        } catch (\Throwable $e) {
            $error        = new GrammarException($e->getMessage(), $e->getCode());
            $error->trace = $this->serializeTrace();

            throw $error;
        }
    }

    /**
     * @param \SplFileInfo $source
     * @return iterable
     * @throws \Throwable
     */
    public function analyze(\SplFileInfo $source): iterable
    {
        $traverser = new Traverser();

        $traverser->with(new IncludesVisitor($source, $this, $this->stack));

        return $traverser->traverse($this->parse($source));
    }

    /**
     * @param \SplFileInfo $source
     * @return iterable
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    private function parse(\SplFileInfo $source): iterable
    {
        return $this->grammar->parse(\fopen($source->getPathname(), 'rb+'));
    }

    /**
     * @return array
     */
    private function serializeTrace(): array
    {
        $trace = [];

        /** @var Expression $expression */
        foreach ($this->stack as $expression) {
            /** @var \SplFileInfo $info */
            $info = $this->stack->getInfo();

            $position = Position::fromOffset(\file_get_contents($info->getPathname()), $expression->getOffset());

            $trace[] = [
                'file'   => $info->getPathname(),
                'line'   => $position->getLine(),
                'column' => $position->getColumn(),
                'info'   => $expression->render(),
            ];
        }

        return $trace;
    }
}
