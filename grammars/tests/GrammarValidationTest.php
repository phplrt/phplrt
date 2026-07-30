<?php

declare(strict_types=1);

namespace Phplrt\Example\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Parser\Analysis\Mode;
use Phplrt\Parser\Analysis\Result\SuccessfulResult;
use Phplrt\Parser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;

final class GrammarValidationTest extends TestCase
{
    /**
     * @throws RuntimeExceptionInterface
     * @throws SourceExceptionInterface
     */
    #[DataProvider('grammarDataProvider')]
    public function testGrammarValidation(FileInterface $grammar): void
    {
        $this->expectNotToPerformAssertions();

        new Compiler()
            ->load($grammar)
            ->build();
    }

    #[DataProvider('exampleDataProvider')]
    public function testSyntaxValidation(Parser $parser, FileInterface $example): void
    {
        $result = $parser->analyze($example, Mode::SyntaxCheck);

        self::assertInstanceOf(SuccessfulResult::class, $result);
    }
}
