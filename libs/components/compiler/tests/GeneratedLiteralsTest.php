<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Source\StringSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Lifecycle\AfterTest;
use Testo\Test;

/**
 * The generated code is laid out the way PHP code is written, while the text
 * of a string literal written by a reducer is its value and reaches the parser
 * exactly as it has been written.
 */
#[Group('phplrt/compiler')]
#[Test]
final class GeneratedLiteralsTest extends TestCase
{
    /**
     * @var list<non-empty-string>
     */
    private array $files = [];

    #[AfterTest]
    protected function tearDown(): void
    {
        foreach ($this->files as $pathname) {
            @\unlink($pathname);
        }

        $this->files = [];
    }

    private static function grammarOf(string $reducer): string
    {
        return "%token T_A a\n\nRule -> {\n" . $reducer . "\n}\n  : <T_A>\n  ;\n";
    }

    private function reduce(string $reducer): mixed
    {
        $pathname = \tempnam(\sys_get_temp_dir(), 'phplrt');

        Assert::string($pathname);

        $this->files[] = $pathname;

        (new Compiler())
            ->load(StringSource::createFromString(self::grammarOf($reducer)))
            ->generate()
            ->save($pathname);

        $parser = require $pathname;

        Assert::instanceOf($parser, ParserInterface::class);

        return $parser->parse(StringSource::createFromString('a'));
    }

    public function testSingleQuotedStringKeepsItsLines(): void
    {
        Assert::same($this->reduce("    return 'first  \n\n\n\nsecond';"), "first  \n\n\n\nsecond");
    }

    public function testDoubleQuotedStringKeepsItsLines(): void
    {
        Assert::same(
            $this->reduce("    \$name = 'x';\n    return \"first \$name  \n\n\n\nsecond\";"),
            "first x  \n\n\n\nsecond",
        );
    }

    public function testHeredocKeepsItsSpacingAndBlankLines(): void
    {
        Assert::same(
            $this->reduce("    \$name = 'x';\n    return <<<TEXT\n    first \$name  \n\n\n\n    second\n    TEXT;"),
            "first x  \n\n\n\nsecond",
        );
    }

    public function testNowdocKeepsItsSpacingAndBlankLines(): void
    {
        Assert::same(
            $this->reduce("    return <<<'TEXT'\n    first  \n\n\n\n      second\n    TEXT;"),
            "first  \n\n\n\n  second",
        );
    }

    public function testHeredocClosedAtTheStartOfTheLineKeepsItsValue(): void
    {
        Assert::same(
            $this->reduce("    return <<<TEXT\nfirst\n  second\nTEXT;"),
            "first\n  second",
        );
    }

    public function testCodeAroundTheLiteralsIsStillLaidOut(): void
    {
        $code = (string) (new Compiler())
            ->load(StringSource::createFromString(self::grammarOf(
                "    \$value = 'first  \n\nsecond';   \n\n\n\n    return \$value;",
            )))
            ->generate();

        Assert::string($code)
            // the line written by the reducer is placed into the method,
            ->contains("\n        \$value = 'first  \n\nsecond';\n")
            // the spaces it is ended with are taken off,
            ->notContains("second';   ")
            // and the blank lines after it are evened out
            ->contains("second';\n\n        return \$value;");
    }
}
