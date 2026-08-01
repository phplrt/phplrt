<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use Phplrt\Compiler\Compiler;
use Phplrt\Lexer\Lexer;
use Phplrt\Parser\Context\TreeBuilder;
use Phplrt\Parser\Parser;
use Phplrt\Parser\ParserConfigsInterface;
use Phplrt\Source\File;

#[Revs(1)]
#[Iterations(5)]
#[RetryThreshold(5)]
#[BeforeMethods('prepare')]
final readonly class PhplrtV3Bench extends PhpDocTypeBenchCase
{
    protected const string TOOL = 'phplrt-v3';

    private Parser $parser;

    protected function boot(string $directory): void
    {
        if (isset($this->parser)) {
            return;
        }

        $compiledParser = self::CACHE_DIRECTORY . '/PhplrtV3Parser.php';

        if (!\is_file($compiledParser)) {
            $compiler = new Compiler();
            $compiler->load(File::fromPathname(self::grammar('PhpDocType.pp2')));

            \file_put_contents($compiledParser, (string) $compiler->build());
        }

        /**
         * @var array{
         *     initial: array-key,
         *     tokens: array{default: array<non-empty-string, non-empty-string>},
         *     skip: list<non-empty-string>,
         *     grammar: array<array-key, \Phplrt\Parser\Grammar\RuleInterface>,
         *     reducers: array<array-key, callable>
         * } $data
         */
        $data = require $compiledParser;

        $this->parser = new Parser(
            new Lexer($data['tokens']['default'], $data['skip']),
            $data['grammar'],
            [
                ParserConfigsInterface::CONFIG_INITIAL_RULE => $data['initial'],
                ParserConfigsInterface::CONFIG_AST_BUILDER => new TreeBuilder($data['reducers']),
            ],
        );
    }

    public function benchCommonType25b(): void
    {
        foreach ($this->types['common-type-25b.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType250b(): void
    {
        foreach ($this->types['common-type-250b.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType1k(): void
    {
        foreach ($this->types['common-type-1k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType10k(): void
    {
        foreach ($this->types['common-type-10k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType100k(): void
    {
        foreach ($this->types['common-type-100k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }

    public function benchCommonType250k(): void
    {
        foreach ($this->types['common-type-250k.txt'] as $type) {
            $this->parser->parse($type);
        }
    }
}
