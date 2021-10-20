<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Compiler\Grammar\PP2Grammar\Builder;
use Phplrt\Compiler\Grammar\PP2Grammar\Lexer;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Grammar\Alternation;
use Phplrt\Grammar\Concatenation;
use Phplrt\Grammar\Lexeme;
use Phplrt\Grammar\Optional;
use Phplrt\Grammar\Repetition;
use Phplrt\Parser\Parser;

class PP2Grammar implements GrammarInterface
{
    /**
     * @var Parser
     */
    private Parser $runtime;

    /**
     * parser constructor.
     */
    public function __construct()
    {
        $this->runtime = new Parser(new Lexer(), $this->grammar(), [
            Parser::CONFIG_INITIAL_RULE => 0,
            Parser::CONFIG_AST_BUILDER  => new Builder(),
        ]);
    }

    /**
     * @return array<RuleInterface>
     */
    private function grammar(): array
    {
        return [
            0  => new Repetition(11, 0),
            1  => new Concatenation([30, 2]),
            2  => new Alternation([22, 24, 5]),
            3  => new Alternation([24, 5]),
            4  => new Repetition(1, 1),
            5  => new Alternation([23, 31, 32, 33, 34]),
            6  => new Alternation([37, 38, 39, 40, 41, 42, 43]),
            7  => new Alternation([21, 22, 24, 5]),
            8  => new Alternation([21, 22, 24, 5]),
            9  => new Optional(29),
            10 => new Alternation([18, 19]),
            11 => new Alternation([15, 16, 13, 14, 17]),
            12 => new Concatenation([25, 27]),
            13 => new Lexeme('T_PRAGMA'),
            14 => new Lexeme('T_INCLUDE'),
            15 => new Lexeme('T_TOKEN_DEF'),
            16 => new Lexeme('T_SKIP_DEF'),
            17 => new Concatenation([10, 20, 28, 8, 9]),
            18 => new Concatenation([26, 27]),
            19 => new Concatenation([27]),
            20 => new Optional(48),
            21 => new Concatenation([2, 4]),
            22 => new Repetition(3, 2),
            23 => new Concatenation([35, 7, 36]),
            24 => new Concatenation([5, 6]),
            25 => new Lexeme('T_ARROW_RIGHT', false),
            26 => new Lexeme('T_KEPT_NAME', false),
            27 => new Lexeme('T_NAME'),
            28 => new Lexeme('T_EQ', false),
            29 => new Lexeme('T_END_OF_RULE', false),
            30 => new Lexeme('T_OR', false),
            31 => new Lexeme('T_TOKEN_SKIPPED'),
            32 => new Lexeme('T_TOKEN_KEPT'),
            33 => new Lexeme('T_TOKEN_STRING'),
            34 => new Lexeme('T_INVOKE'),
            35 => new Lexeme('T_GROUP_OPEN', false),
            36 => new Lexeme('T_GROUP_CLOSE', false),
            37 => new Lexeme('T_REPEAT_ZERO_OR_ONE'),
            38 => new Lexeme('T_REPEAT_ONE_OR_MORE'),
            39 => new Lexeme('T_REPEAT_ZERO_OR_MORE'),
            40 => new Lexeme('T_REPEAT_N_TO_M'),
            41 => new Lexeme('T_REPEAT_ZERO_TO_M'),
            42 => new Lexeme('T_REPEAT_N_OR_MORE'),
            43 => new Lexeme('T_REPEAT_EXACTLY_N'),
            44 => new Concatenation([45, 47, 46]),
            45 => new Lexeme('T_PHP_OPEN', false),
            46 => new Lexeme('T_PHP_CLOSE', false),
            47 => new Lexeme('T_PHP_CODE'),
            48 => new Alternation([12, 44]),
        ];
    }

    /**
     * {@inheritDoc}
     * @throws \Throwable
     */
    public function parse($source, array $options = []): iterable
    {
        return $this->runtime->parse($source, $options);
    }
}
