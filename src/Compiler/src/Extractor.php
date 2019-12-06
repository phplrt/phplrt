<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use PhpParser\Parser;
use Phplrt\Source\File;
use PhpParser\Node\Stmt;
use PhpParser\Node\Name;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\PrettyPrinter\Standard;
use Phplrt\Compiler\Extractor\AliasVisitor;
use Phplrt\Source\Exception\NotFoundException;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Compiler\Extractor\DependenciesVisitor;

/**
 * Class Extractor
 */
class Extractor
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Standard
     */
    private $printer;

    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $replaces = [];

    /**
     * Importer constructor.
     */
    public function __construct()
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $this->printer = new Standard();
    }

    /**
     * @param string $fqn
     * @param string $alias
     * @return void
     */
    public function replace(string $fqn, string $alias): void
    {
        $this->replaces[$fqn] = $alias;
    }

    /**
     * @param string $fqn
     * @return string
     * @throws NotFoundException
     * @throws NotReadableException
     * @throws \ReflectionException
     */
    public function get(string $fqn): string
    {
        if (! isset($this->classes[$fqn = \trim($fqn, '\\')])) {
            $this->loadClass($fqn);
        }

        $copy = static function (Stmt\ClassLike $stmt): ClassLike {
            return clone $stmt;
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AliasVisitor($this->replaces));
        $traverser->traverse($result = \array_map($copy, $this->classes));

        return $this->print([$result[$fqn]]);
    }

    /**
     * @param string $class
     * @return void
     * @throws NotFoundException
     * @throws NotReadableException
     * @throws \ReflectionException
     */
    public function loadClass(string $class): void
    {
        $file = File::fromPathname($this->getPathname($class));

        $this->loadSource($file->getContents());
    }

    /**
     * @param string $class
     * @return string
     * @throws \ReflectionException
     */
    private function getPathname(string $class): string
    {
        return (new \ReflectionClass($class))->getFileName();
    }

    /**
     * @param string $source
     * @return void
     */
    public function loadSource(string $source): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor($dependencies = new DependenciesVisitor());
        $traverser->traverse($this->parser->parse($source));

        foreach ($dependencies->getClasses() as $fqn => $ast) {
            $this->classes[(new Name($fqn))->toString()] = $ast;
        }
    }

    /**
     * @param array|Stmt[] $statements
     * @return string
     */
    private function print(array $statements): string
    {
        return $this->printer->prettyPrint($statements);
    }

    /**
     * @param object $object
     * @return void
     * @throws NotFoundException
     * @throws NotReadableException
     * @throws \ReflectionException
     */
    public function loadObject($object): void
    {
        $this->loadClass(\get_class($object));
    }
}
