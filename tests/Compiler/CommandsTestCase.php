<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Compiler;

use Phplrt\Compiler\Console\CompileCommand;
use Phplrt\Compiler\Console\GrammarCompileCommand;
use Phplrt\Compiler\Grammar\Parser;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class CommandsTestCase
 */
class CommandsTestCase extends TestCase
{
    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testCompileGrammarCommand(): void
    {
        $tester = new CommandTester($cmd = new GrammarCompileCommand());

        $file = (new \ReflectionClass(Parser::class))->getFileName();
        $src  = \file_get_contents($file);

        $tester->execute([]);

        $this->assertNotSame($src, \file_get_contents($file));
    }

    /**
     * @return void
     */
    public function testCompileCommand(): void
    {
        $tester = new CommandTester($cmd = new CompileCommand());

        $tester->execute([
            'grammar' => __DIR__ . '/grammar.pp2',
            '--dir'   => __DIR__ . '/out',
            '--class' => 'generated',
        ]);

        $this->assertFileExists(__DIR__ . '/out/generated.php');
    }
}
