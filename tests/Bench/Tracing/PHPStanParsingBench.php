<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Tracing;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

#[Warmup(1)]
#[Revs(2)]
#[Iterations(3)]
#[RetryThreshold(0.3)]
#[BeforeMethods('prepare')]
final readonly class PHPStanParsingBench extends ParsingBench
{
    private Lexer $phpStanLexer;
    private TypeParser $phpStanParser;

    public function prepare(): void
    {
        $config = new ParserConfig(usedAttributes: []);
        $this->phpStanLexer = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $this->phpStanParser = new TypeParser($config, $constExprParser);
    }

    public function benchParsing(): void
    {
        $tokenizer = $this->phpStanLexer->tokenize(self::SAMPLE);

        $this->phpStanParser->parse(new TokenIterator($tokenizer));
    }
}
