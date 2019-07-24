<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler;

use Phplrt\Assembler\Exception\ParsingException;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * Class Parser
 */
class Parser implements ParserInterface
{
    /**
     * @var \PhpParser\Parser
     */
    private $parser;

    /**
     * Parser constructor.
     */
    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
    }

    /**
     * @param string $sources
     * @param array $visitors
     * @return iterable|Node[]
     */
    public function parse(string $sources, array $visitors = []): iterable
    {
        try {
            $ast = $this->parser->parse($sources);

            return \count($visitors) ? $this->modify($ast, $visitors) : $ast;
        } catch (Error $error) {
            throw new ParsingException($error->getMessage(), $error->getCode());
        }
    }

    /**
     * @param iterable|Node[] $ast
     * @param array|NodeVisitorAbstract[] $visitors
     * @return iterable
     */
    public function modify(iterable $ast, array $visitors): iterable
    {
        $traverser = new NodeTraverser();

        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        return $traverser->traverse($ast);
    }
}
