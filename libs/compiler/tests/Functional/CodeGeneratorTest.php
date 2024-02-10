<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests\Functional;

use Phplrt\Compiler\Compiler;
use Phplrt\Parser\Parser;

class CodeGeneratorTest extends TestCase
{
    public function testOutput(): void
    {
        $generator = (new Compiler())
            ->load(\file_get_contents(__DIR__ . '/resources/input.pp2'))
            ->build()
            ->withClassUsage(Parser::class);

        $source = $this->formatText(\file_get_contents(__DIR__ . '/resources/output.php'));

        $this->assertSame($source, $this->formatText($generator->generate()));
    }

    private function formatText(string $text): string
    {
        $lines = \explode("\n", \trim($text));

        foreach ($lines as $i => $line) {
            $lines[$i] = \trim($line);
        }

        return \implode("\n", $lines);
    }
}
