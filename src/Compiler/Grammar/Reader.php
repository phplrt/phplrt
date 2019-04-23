<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Io\Readable;
use Phplrt\Parser\Grammar;
use Phplrt\Parser\Driver\Llk;
use Phplrt\Lexer\LexerInterface;
use Phplrt\Parser\ParserInterface;
use Phplrt\Parser\GrammarInterface;
use Phplrt\Lexer\Driver\NativeRegex;
use Phplrt\Exception\ExternalException;
use Phplrt\Io\Exception\NotReadableException;
use Phplrt\Compiler\Grammar\Delegate\RuleDelegate;
use Phplrt\Compiler\Grammar\Delegate\TokenDelegate;
use Phplrt\Compiler\Grammar\Delegate\IncludeDelegate;

/**
 * Class Reader
 */
class Reader
{
    /**
     * @var Readable
     */
    private $file;

    /**
     * @var ParserInterface
     */
    private $pp;

    /**
     * @var LexerInterface
     */
    private $lexer;

    /**
     * @var GrammarInterface
     */
    private $grammar;

    /**
     * @var Analyzer
     */
    private $analyzer;

    /**
     * Reader constructor.
     *
     * @param Readable $file
     */
    public function __construct(Readable $file)
    {
        $this->file = $file;
        $this->pp = new Parser();
        $this->lexer = new NativeRegex();
        $this->grammar = new Grammar();
        $this->analyzer = new Analyzer();
    }

    /**
     * @return ParserInterface
     * @throws ExternalException
     * @throws NotReadableException
     */
    public function getParser(): ParserInterface
    {
        $this->addGrammar($this->file);

        foreach ($this->analyzer->analyze() as $rule) {
            $this->grammar->addRule($rule);
        }

        return new Llk($this->lexer, $this->grammar);
    }

    /**
     * @param Readable $file
     * @throws ExternalException
     * @throws NotReadableException
     */
    private function addGrammar(Readable $file): void
    {
        $ast = $this->pp->parse($file);

        foreach ($ast->getChildren() as $child) {
            switch (true) {
                case $child instanceof IncludeDelegate:
                    $this->addGrammar($child->getPathname($file));
                    break;

                case $child instanceof TokenDelegate:
                    $this->lexer->add($child->getTokenName(), $child->getTokenPattern());
                    if (! $child->isKept()) {
                        $this->lexer->skip($child->getTokenName());
                    }
                    break;

                case $child instanceof RuleDelegate:
                    $this->analyzer->addRuleDelegate($child);
                    if ($child->getDelegate()) {
                        $this->grammar->addDelegate($child->getRuleName(), $child->getDelegate());
                    }
                    break;
            }
        }
    }
}
