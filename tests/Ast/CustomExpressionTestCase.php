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
use Phplrt\Tests\Ast\Helper\AstAssertionTrait;

/**
 * Class CustomExpressionTestCase
 */
class CustomExpressionTestCase extends TestCase
{
    use AstAssertionTrait;

    /**
     * @return void
     */
    public function testRenderable(): void
    {
        $this->assertAst('
            <ArrayIterator>
                <stdClass/>
                <Leaf offset="0">value</Leaf>
                <stdClass public="public"/>
            </ArrayIterator>
        ',
            new \ArrayIterator([
                new \stdClass(),
                new Leaf('Leaf', 'value'),
                (object)[
                    "\0stdClass\0private" => 'private',
                    "\0*\0protected"      => 'protected',
                    'public'              => 'public',
                ],
            ])
        );
    }
}
