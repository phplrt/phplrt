<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Visitor\Traverser;
use Phplrt\Visitor\Visitor;
use Phplrt\Visitor\VisitorInterface;

/**
 * Class Dumper
 */
class Dumper
{
    /**
     * @param iterable $ast
     * @return string
     */
    public function dump(iterable $ast): string
    {
        $visitor = $this->visitor();

        (new Traverser())
            ->with($visitor)
            ->traverse($ast);

        return \implode("\n", $visitor->getResult());
    }

    /**
     * @return VisitorInterface
     */
    private function visitor(): VisitorInterface
    {
        return new class() extends Visitor {
            /**
             * @var array|string[]
             */
            private $result = [];

            /**
             * @var int
             */
            private $depth = 1;

            /**
             * @param NodeInterface $node
             * @return mixed|void|null
             */
            public function enter(NodeInterface $node): void
            {
                $suffix = ' { ' . $this->suffixOf($node) . ' };';

                $this->result[] = $this->prefix() . \get_class($node) . $suffix;
            }

            /**
             * @param NodeInterface $node
             * @return string
             */
            private function suffixOf(NodeInterface $node): string
            {
                $result = [];

                foreach ($this->props($node) as $name => $value) {
                    $result[] = $name . ' = ' . $value;
                }

                return \implode(', ', $result);
            }

            /**
             * @param NodeInterface $node
             * @return array
             */
            private function props(NodeInterface $node): array
            {
                $result = [];

                foreach ($this->reflection($node) as $prop) {
                    $value = $prop->getValue($node);

                    switch (true) {
                        case \is_scalar($value):
                            $result[$prop->getName()] = \is_float($value) && (\is_infinite($value) || \is_nan($value))
                                ? $value
                                : \json_encode($value);
                            break;

                        case \is_object($value):
                            $result[$prop->getName()] = \get_class($value);
                            break;

                        default:
                            $result[$prop->getName()] = \gettype($value);
                    }
                }

                return $result;
            }

            /**
             * @param NodeInterface $node
             * @return iterable|\ReflectionProperty[]
             */
            private function reflection(NodeInterface $node): iterable
            {
                return (new \ReflectionClass($node))->getProperties(\ReflectionProperty::IS_PUBLIC);
            }

            /**
             * @return string
             */
            private function prefix(): string
            {
                return \str_repeat('    ', $this->depth++);
            }

            /**
             * @param NodeInterface $node
             * @return void
             */
            public function leave(NodeInterface $node): void
            {
                --$this->depth;
            }

            /**
             * @return array|string[]
             */
            public function getResult(): array
            {
                return $this->result;
            }
        };
    }
}
