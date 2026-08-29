<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Loader\SyntaxLoaderRegistry;
use Phplrt\Compiler\Syntax\PP\PPLoader;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class SyntaxLoaderRegistryTest extends TestCase
{
    public function testFormatIsToldByTheExtension(): void
    {
        $registry = new SyntaxLoaderRegistry();

        Assert::instanceOf($registry->selectFor(VirtualSource::createEmpty('/app/a.pp')), PPLoader::class);
        Assert::instanceOf($registry->selectFor(VirtualSource::createEmpty('/app/a.pp2')), PP2Loader::class);
        Assert::instanceOf($registry->selectFor(VirtualSource::createEmpty('/app/a.pp3')), PP3Loader::class);
    }

    public function testGrammarOfNoFileIsReadAsTheNewestFormat(): void
    {
        $registry = new SyntaxLoaderRegistry();

        Assert::instanceOf($registry->selectFor(StringSource::createEmpty()), PP3Loader::class);
    }

    public function testGrammarOfAnUnknownExtensionIsReadAsTheNewestFormat(): void
    {
        $registry = new SyntaxLoaderRegistry();

        Assert::instanceOf($registry->selectFor(VirtualSource::createEmpty('/app/a.txt')), PP3Loader::class);
        Assert::instanceOf($registry->selectFor(VirtualSource::createEmpty('/app/grammar')), PP3Loader::class);
    }

    public function testExtensions(): void
    {
        Assert::same(new SyntaxLoaderRegistry()->extensions, ['pp', 'pp2', 'pp3']);
    }
}
