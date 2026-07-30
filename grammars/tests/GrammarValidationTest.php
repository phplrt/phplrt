<?php

declare(strict_types=1);

namespace Phplrt\Example\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Source\Exception\SourceExceptionInterface;
use Phplrt\Contracts\Source\FileInterface;
use PHPUnit\Framework\Attributes\DataProvider;

final class GrammarValidationTest extends TestCase
{
    /**
     * @param list<FileInterface> $_
     * @throws RuntimeExceptionInterface
     * @throws SourceExceptionInterface
     */
    #[DataProvider('grammarDataProvider')]
    public function testValidation(FileInterface $grammar, array $_): void
    {
        $this->expectNotToPerformAssertions();

        new Compiler()
            ->load($grammar)
            ->build();
    }
}
