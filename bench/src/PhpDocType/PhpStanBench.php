<?php

declare(strict_types=1);

namespace Phplrt\Bench\PhpDocType;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

#[Revs(1)]
#[Iterations(5)]
#[RetryThreshold(5)]
#[BeforeMethods('prepare')]
final readonly class PhpStanBench extends PhpDocTypeBenchCase
{
    protected const string TOOL = 'phpstan';

    private Lexer $lexer;

    private TypeParser $parser;

    protected function boot(string $directory): void
    {
        if (isset($this->parser)) {
            return;
        }

        $config = new ParserConfig([]);

        $this->lexer = new Lexer($config);
        $this->parser = new TypeParser($config, new ConstExprParser($config));
    }

    public function benchCommonType25b(): void
    {
        foreach ($this->types['common-type-25b.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }

    public function benchCommonType250b(): void
    {
        foreach ($this->types['common-type-250b.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }

    public function benchCommonType1k(): void
    {
        foreach ($this->types['common-type-1k.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }

    public function benchCommonType10k(): void
    {
        foreach ($this->types['common-type-10k.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }

    public function benchCommonType100k(): void
    {
        foreach ($this->types['common-type-100k.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }

    public function benchCommonType250k(): void
    {
        foreach ($this->types['common-type-250k.txt'] as $type) {
            $this->parser->parse(new TokenIterator(
                $this->lexer->tokenize($type),
            ));
        }
    }
}
