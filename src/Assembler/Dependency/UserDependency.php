<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\Dependency;

use PhpParser\Node\Name;
use Phplrt\Assembler\ParserInterface;
use Phplrt\Assembler\NameResolver\Visitor;

/**
 * Class UserDependency
 */
abstract class UserDependency extends Dependency implements UserDependencyInterface
{
    /**
     * @var array|Name[]
     */
    private $dependencies;

    /**
     * @var ParserInterface
     */
    protected $parser;

    /**
     * UserDependency constructor.
     *
     * @param string $fqn
     * @param ParserInterface $parser
     */
    public function __construct($fqn, ParserInterface $parser)
    {
        $this->parser = $parser;
        parent::__construct($fqn);
    }

    /**
     * @return iterable|Name[]
     */
    public function getDependencies(): iterable
    {
        if ($this->dependencies === null) {
            $this->dependencies = [];

            $this->lookup(function (Name $name) {
                $fqn = $name->toString();

                if (! isset($result[$fqn])) {
                    $this->dependencies[$fqn] = $name;
                }

                return $name;
            });
        }

        return $this->dependencies;
    }

    /**
     * @param \Closure $closure
     * @return iterable
     */
    public function lookup(\Closure $closure): iterable
    {
        return $this->parser->modify($this->getAst(), [
            new Visitor($closure),
        ]);
    }
}
