<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Ast;

use Phplrt\Ast\Leaf;
use Phplrt\Ast\Node;
use Phplrt\Contracts\Ast\LeafInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Tests\Ast\Helper\AstAssertionTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class RuleTestCase
 */
class RuleTestCase extends TestCase
{
    use AstAssertionTrait;

    /**
     * @return NodeInterface
     */
    private function rule(): NodeInterface
    {
        return new Node('a', [
            new Leaf('b', 'b', 42),
            new Leaf('c', 'c', 42),
            new Node('d', [
                new Leaf('e', 'e', 42),
            ]),
        ], 42);
    }

    /**
     * @throws ExpectationFailedException
     */
    public function testChildren(): void
    {
        $rule = $this->rule();

        $this->assertCount(\count($rule), $rule);
        $this->assertCount(\count($rule->getChildren()), $rule->getChildren());

        foreach ($rule as $child) {
            $this->assertInstanceOf(NodeInterface::class, $child);
        }
    }

    /**
     * @return void
     * @throws ExpectationFailedException
     */
    public function testChild(): void
    {
        $rule = $this->rule();

        $this->assertInstanceOf(LeafInterface::class, $rule->getChild(0));
        $this->assertInstanceOf(LeafInterface::class, $rule->getChild(1));
        $this->assertInstanceOf(NodeInterface::class, $rule->getChild(2));
        $this->assertNull($rule->getChild(3));
    }

    /**
     * @return void
     */
    public function testRenderable(): void
    {
        $this->assertAst('
            <a offset="42">
                <b offset="42">b</b>
                <c offset="42">c</c>
                <d offset="0">
                    <e offset="42">e</e>
                </d>
            </a>
        ', (string)$this->rule());
    }
}
