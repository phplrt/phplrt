<?php

declare(strict_types=1);

namespace Phplrt\Tests\Bench\Lexer;

use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Lexer\Lexer;

#[Warmup(5)]
#[Revs(1000)]
#[Iterations(7)]
#[RetryThreshold(0.3)]
#[BeforeMethods('prepare')]
final readonly class PhplrtLexerBench extends LexerBench
{
    private LexerInterface $phplrt;

    #[\Override]
    public function prepare(): void
    {
        parent::prepare();

        $this->phplrt = new readonly class extends Lexer {
            public const int T_DQ_STRING_LITERAL = 0;
            public const int T_SQ_STRING_LITERAL = 1;
            public const int T_PFX_FLOAT_LITERAL = 2;
            public const int T_SFX_FLOAT_LITERAL = 3;
            public const int T_EXP_LITERAL = 4;
            public const int T_BIN_INT_LITERAL = 5;
            public const int T_OCT_INT_LITERAL = 6;
            public const int T_HEX_INT_LITERAL = 7;
            public const int T_DEC_INT_LITERAL = 8;
            public const int T_BOOL_LITERAL = 9;
            public const int T_NULL_LITERAL = 10;
            public const int T_VARIABLE = 14;
            public const int T_NAME_WITH_SPACE = 15;
            public const int T_NAME = 16;
            public const int T_COMMENT = 40;
            public const int T_DOC_COMMENT = 41;

            public function __construct()
            {
                parent::__construct(
                    pattern: '/\\G(?|(?:(?:"(?:[^"\\\\]*(?:\\\\.[^"\\\\]*)*)")(*MARK:0))|(?:(?:\'(?:[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')(*MARK:1))|(?:(?:\\-?(?i)[0-9]++\\.[0-9]*+(?:e-?[0-9]++)?)(*MARK:2))|(?:(?:\\-?(?i)[0-9]*+\\.[0-9]++(?:e-?[0-9]++)?)(*MARK:3))|(?:(?:\\-?(?i)[0-9]++e-?[0-9]++)(*MARK:4))|(?:(?:\\-?(?i)0b[0-1_]++)(*MARK:5))|(?:(?:\\-?(?i)0o[0-7_]++)(*MARK:6))|(?:(?:\\-?(?i)0x[0-9a-f_]++)(*MARK:7))|(?:(?:\\-?(?i)[0-9][0-9_]*+)(*MARK:8))|(?:(?:(?i)(?:true|false)\\b)(*MARK:9))|(?:(?:(?i)(?:null)(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:10))|(?:(?:(?i)is\\h+not(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:11))|(?:(?:(?i)is(?![a-zA-Z0-9\\-_\\x80-\\xff]))(*MARK:12))|(?:(?:\\$this\\b)(*MARK:13))|(?:(?:\\$[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*)(*MARK:14))|(?:(?:[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*\\s+?)(*MARK:15))|(?:(?:[a-zA-Z_\\x80-\\xff][a-zA-Z0-9\\-_\\x80-\\xff]*)(*MARK:16))|(?:(?:\\<\\=)(*MARK:17))|(?:(?:\\>\\=)(*MARK:18))|(?:(?:\\<)(*MARK:19))|(?:(?:\\>)(*MARK:20))|(?:(?:\\()(*MARK:21))|(?:(?:\\))(*MARK:22))|(?:(?:\\{)(*MARK:23))|(?:(?:\\})(*MARK:24))|(?:(?:\\#\\[)(*MARK:25))|(?:(?:\\[)(*MARK:26))|(?:(?:\\])(*MARK:27))|(?:(?:,)(*MARK:28))|(?:(?:\\.\\.\\.)(*MARK:29))|(?:(?:;)(*MARK:30))|(?:(?:\\:\\:)(*MARK:31))|(?:(?:\\:)(*MARK:32))|(?:(?:\\=)(*MARK:33))|(?:(?:\\\\)(*MARK:34))|(?:(?:\\?)(*MARK:35))|(?:(?:\\!)(*MARK:36))|(?:(?:\\|)(*MARK:37))|(?:(?:&)(*MARK:38))|(?:(?:\\\\\\*)(*MARK:39))|(?:(?:(?:\\/\\/|\\#).+?$)(*MARK:40))|(?:(?:\\/\\*.*?\\*\\/)(*MARK:41))|(?:(?:\\s++)(*MARK:42))|(?:(?:.+?)(*MARK:43)))/Ssum',
                    channels: [42 => 'hidden', 'unknown'],
                    names: [
                        'T_DQ_STRING_LITERAL',
                        'T_SQ_STRING_LITERAL',
                        'T_PFX_FLOAT_LITERAL',
                        'T_SFX_FLOAT_LITERAL',
                        'T_EXP_LITERAL',
                        'T_BIN_INT_LITERAL',
                        'T_OCT_INT_LITERAL',
                        'T_HEX_INT_LITERAL',
                        'T_DEC_INT_LITERAL',
                        'T_BOOL_LITERAL',
                        'T_NULL_LITERAL',
                        14 => 'T_VARIABLE',
                        'T_NAME_WITH_SPACE',
                        'T_NAME',
                        40 => 'T_COMMENT',
                        'T_DOC_COMMENT',
                    ],
                );
            }
        };
    }

    public function benchLexicalAnalysis(): void
    {
        $this->phplrt->lex($this->source);
    }
}
