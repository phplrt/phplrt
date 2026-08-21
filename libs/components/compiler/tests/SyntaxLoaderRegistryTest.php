<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Loader\SyntaxLoaderRegistry;
use Phplrt\Compiler\Syntax\PP\PPLoader;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[Group('phplrt/compiler')]
final class SyntaxLoaderRegistryTest extends TestCase
{
    #[TestDox('The format of a grammar is told by the extension of its file')]
    public function testFormatIsToldByTheExtension(): void
    {
        $registry = new SyntaxLoaderRegistry();

        self::assertInstanceOf(PPLoader::class, $registry->selectFor(new VirtualSource('/app/a.pp', new StringSource())));
        self::assertInstanceOf(PP2Loader::class, $registry->selectFor(new VirtualSource('/app/a.pp2', new StringSource())));
        self::assertInstanceOf(PP3Loader::class, $registry->selectFor(new VirtualSource('/app/a.pp3', new StringSource())));
    }

    #[TestDox('A grammar written in no file is read as the newest format there is')]
    public function testGrammarOfNoFileIsReadAsTheNewestFormat(): void
    {
        $registry = new SyntaxLoaderRegistry();

        self::assertInstanceOf(PP3Loader::class, $registry->selectFor(new StringSource('')));
    }

    #[TestDox('A file named with an extension of no format is read as the newest one')]
    public function testGrammarOfAnUnknownExtensionIsReadAsTheNewestFormat(): void
    {
        $registry = new SyntaxLoaderRegistry();

        self::assertInstanceOf(PP3Loader::class, $registry->selectFor(new VirtualSource('/app/a.txt', new StringSource())));
        self::assertInstanceOf(PP3Loader::class, $registry->selectFor(new VirtualSource('/app/grammar', new StringSource())));
    }

    #[TestDox('The extensions a grammar file may be named with are known')]
    public function testExtensions(): void
    {
        self::assertSame(['pp', 'pp2', 'pp3'], new SyntaxLoaderRegistry()->extensions);
    }
}
