<?php
/**
 * This file is part of compiler package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Tests\Parser;

use Phplrt\Contracts\Source\Readable;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Dumper\HoaDumper;
use Phplrt\Source\Exception\NotReadableException;
use Phplrt\Source\File;
use PHPUnit\Framework\Exception;

/**
 * Class PP2ParserTestCase
 */
class ParserTestCase extends TestCase
{
    /**
     * @return array
     * @throws NotReadableException
     */
    public function grammars(): array
    {
        $sources = [
            'PP2'  => [new PP2Llk(), __DIR__ . '/pp2/*.pp2'],
            'JSON' => [new JsonLlk(), __DIR__ . '/json/*.json'],
            'SDL'  => [new SdlLlk(), __DIR__ . '/sdl/*.graphqls'],
        ];

        $result = [];

        foreach ($sources as $name => [$parser, $directory]) {
            foreach (\glob($directory) as $file) {
                $result[$name . ':' . \basename($file)] = [$parser, File::fromPathname($file)];
            }
        }

        return $result;
    }

    /**
     * @dataProvider grammars
     *
     * @param ParserInterface $parser
     * @param Readable $file
     * @throws Exception
     */
    public function testCompareAst(ParserInterface $parser, Readable $file): void
    {
        $ast = $parser->parse($file);

        $content = \file_get_contents($file->getPathname() . '.txt');

        $this->assertSame(
            \trim(\str_replace("\r\n", "\n", $content)),
            \trim(\str_replace("\r\n", "\n", (new HoaDumper())->dump($ast)))
        );
    }
}
