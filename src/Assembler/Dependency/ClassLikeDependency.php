<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency;

use Phplrt\Assembler\Dependency\Reader\ClassFilter;
use Phplrt\Assembler\Dependency\Reader\EntryAnalyzer;
use Phplrt\Assembler\ParserInterface;
use PhpParser\Node;
use PhpParser\Node\Name;

/**
 * Class ClassLikeDependency
 */
abstract class ClassLikeDependency extends UserDependency
{
    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * ClassDependency constructor.
     *
     * @param string|Name $fqn
     * @param ParserInterface $parser
     * @throws \ReflectionException
     */
    public function __construct($fqn, ParserInterface $parser)
    {
        parent::__construct($fqn, $parser);

        $this->reflection = new \ReflectionClass($this->getName());
    }

    /**
     * @return iterable|Node[]
     */
    public function getAst(): iterable
    {
        return $this->parser->parse(\file_get_contents($this->reflection->getFileName()), [
            new EntryAnalyzer(),
            new ClassFilter($this->reflection),
        ]);
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->reflection->getFileName();
    }
}
