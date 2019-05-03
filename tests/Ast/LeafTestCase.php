<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Ast;

use Phplrt\Ast\Leaf;
use Phplrt\Ast\LeafInterface;
use Phplrt\Tests\Ast\Helper\AstAssertionTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Class LeafTestCase
 */
class LeafTestCase extends TestCase
{
    use AstAssertionTrait;

    /**
     * @throws ExpectationFailedException
     */
    public function testLeafName(): void
    {
        $leaf = new Leaf('name', 'value', 42);

        $this->assertEquals('name', $leaf->getName());
    }

    /**
     * @throws ExpectationFailedException
     */
    public function testLeafValue(): void
    {
        $leaf = new Leaf('name', 'value', 42);

        $this->assertEquals('value', $leaf->getValue());
    }

    /**
     * @throws ExpectationFailedException
     */
    public function testLeafOffset(): void
    {
        $leaf = new Leaf('name', 'value', 42);

        $this->assertEquals(42, $leaf->getOffset());
    }

    /**
     * @return void
     */
    public function testRenderable(): void
    {
        $leaf = new Leaf('name', 'value', 42);
        $this->assertAst('<name offset="42">value</name>', (string)$leaf);

        $leaf = new Leaf('name', '<tag>', 42);
        $this->assertAst('<name offset="42">&lt;tag&gt;</name>', (string)$leaf);
    }
}
