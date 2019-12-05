<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar;

use Phplrt\Parser\Parser;
use Phplrt\Grammar\Lexeme;
use Phplrt\Lexer\Multistate;
use Phplrt\Grammar\Optional;
use Phplrt\Compiler\Ast\Node;
use Phplrt\Grammar\Repetition;
use Phplrt\Grammar\Alternation;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Grammar\Concatenation;
use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Parser\Builder\Expandable;
use Phplrt\Compiler\Ast\Def\PragmaDef;
use Phplrt\Compiler\Ast\Stmt\RuleStmt;
use Phplrt\Compiler\Ast\Stmt\TokenStmt;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Stmt\Quantifier;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Compiler\Ast\Stmt\DelegateStmt;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Contracts\Lexer\BufferInterface;
use Phplrt\Compiler\Ast\Stmt\RepetitionStmt;
use Phplrt\Compiler\Ast\Stmt\AlternationStmt;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Compiler\Ast\Stmt\ClassDelegateStmt;
use Phplrt\Compiler\Ast\Stmt\ConcatenationStmt;

/**
 * Class GrammarParser
 */
class PP2Grammar extends Parser implements GrammarInterface
{
    /**
     * Parser constructor.
     */
    public function __construct()
    {
        $lexer = new Multistate([
            0 => new PP2Lexer(),
            1 => new PP2PHPLexer(new PhpLexer()),
        ], [
            0 => ['T_PHP_OPEN' => 1],
            1 => ['T_PHP_CODE' => 0],
        ]);

        parent::__construct($lexer, $this->grammar(), [
            self::CONFIG_INITIAL_RULE => 0,
            self::CONFIG_AST_BUILDER  => new Expandable($this->reducers()),
        ]);
    }

    /**
     * @return array|RuleInterface[]
     */
    private function grammar(): array
    {
        return [
            0  => new Repetition(11, 0),
            11 => new Alternation([
                15,
                16,
                13,
                14,
                17,
            ]),
            13 => new Lexeme('T_PRAGMA'),
            14 => new Lexeme('T_INCLUDE'),
            15 => new Lexeme('T_TOKEN_DEF'),
            16 => new Lexeme('T_SKIP_DEF'),
            17 => new Concatenation([10, 20, 28, 8, 9]),
            10 => new Alternation([
                18,
                19,
            ]),
            18 => new Concatenation([26, 27]),
            19 => new Concatenation([27]),
            20 => new Optional(48),
            12 => new Concatenation([25, 27]),
            25 => new Lexeme('T_ARROW_RIGHT', false),
            26 => new Lexeme('T_KEPT_NAME', false),
            27 => new Lexeme('T_NAME'),
            28 => new Lexeme('T_EQ', false),
            8  => new Alternation([21, 22, 24, 5]),
            9  => new Optional(29),
            29 => new Lexeme('T_END_OF_RULE', false),
            21 => new Concatenation([2, 4]),
            4  => new Repetition(1, 1),
            1  => new Concatenation([30, 2]),
            30 => new Lexeme('T_OR', false),
            2  => new Alternation([22, 24, 5]),
            22 => new Repetition(3, 2),
            3  => new Alternation([24, 5]),
            5  => new Alternation([
                23,
                31,
                32,
                33,
                34,
            ]),
            31 => new Lexeme('T_TOKEN_SKIPPED'),
            32 => new Lexeme('T_TOKEN_KEPT'),
            33 => new Lexeme('T_TOKEN_STRING'),
            34 => new Lexeme('T_INVOKE'),
            23 => new Concatenation([35, 7, 36]),
            35 => new Lexeme('T_GROUP_OPEN', false),
            36 => new Lexeme('T_GROUP_CLOSE', false),
            7  => new Alternation([21, 22, 24, 5]),
            24 => new Concatenation([5, 6]),
            6  => new Alternation([
                37,
                38,
                39,
                40,
                41,
                42,
                43,
            ]),
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
     * @return array|\Closure[]
     */
    private function reducers(): array
    {
        return [
            20 => static function (array $delegates): NodeInterface {
                if (\count($delegates) === 0) {
                    return new DelegateStmt(null);
                }

                $delegate = \reset($delegates);

                if ($delegate->getName() === 'T_PHP_CODE') {
                    return new DelegateStmt(\trim($delegate->getValue()));
                }

                return new ClassDelegateStmt($delegate->getValue());
            },
            14 => static function (Composite $include): NodeInterface {
                return new IncludeExpr($include[0]->getValue());
            },
            13 => static function (Composite $pragma): NodeInterface {
                return new PragmaDef($pragma[0]->getValue(), $pragma[1]->getValue());
            },
            15 => static function (Composite $token): NodeInterface {
                /** @var TokenInterface[] $token */
                [$state, $name, $pattern, $next] = $token;

                $result = new TokenDef($name->getValue(), $pattern->getValue());

                if ($state->getValue()) {
                    $result->state = $state->getValue();
                }

                if ($next) {
                    $result->next = $next->getValue();
                }

                return $result;
            },
            16 => static function (Composite $skip): NodeInterface {
                return new TokenDef($skip[0]->getValue(), $skip[1]->getValue(), false);
            },
            17 => static function (array $sequence): NodeInterface {
                [$name, $keep, $delegate, $stmt] = $sequence;

                return new RuleDef($name, $delegate, $stmt, $keep);
            },
            18 => static function (array $name): array {
                return [$name[0]->getValue(), true];
            },
            19 => static function (array $name): array {
                return [$name[0]->getValue(), false];
            },
            27 => static function (TokenInterface $name): array {
                return [$name];
            },
            34 => static function (Composite $invocation): NodeInterface {
                return new RuleStmt($invocation[0]->getValue());
            },
            32 => static function (Composite $token): NodeInterface {
                return new TokenStmt($token[0]->getValue(), true);
            },
            31 => static function (Composite $skip): NodeInterface {
                return new TokenStmt($skip[0]->getValue(), false);
            },
            33 => static function (Composite $invocation): NodeInterface {
                return new PatternStmt($invocation[0]->getValue());
            },
            21 => static function (array $statements): NodeInterface {
                return new AlternationStmt($statements);
            },
            22 => static function (array $statements): NodeInterface {
                return new ConcatenationStmt($statements);
            },
            24 => static function (array $payload): NodeInterface {
                [$stmt, $q] = $payload;

                return new RepetitionStmt($stmt, $q);
            },
            23 => static function (array $group): NodeInterface {
                return \reset($group);
            },
            37 => static function (): NodeInterface {
                return new Quantifier(0, 1);
            },
            38 => static function (): NodeInterface {
                return new Quantifier(1, \INF);
            },
            39 => static function (): NodeInterface {
                return new Quantifier(0, \INF);
            },
            40 => static function (Composite $value): NodeInterface {
                [$from, $to] = [$value[0]->getValue(), $value[1]->getValue()];

                return new Quantifier((int)$from, (int)$to);
            },
            42 => static function (Composite $value): NodeInterface {
                return new Quantifier((int)$value[0]->getValue(), \INF);
            },
            41 => static function (Composite $value): NodeInterface {
                return new Quantifier(0, (int)$value[0]->getValue());
            },
            43 => static function (Composite $value): NodeInterface {
                $count = (int)$value[0]->getValue();

                return new Quantifier($count, $count);
            },
        ];
    }

    /**
     * @param ReadableInterface $source
     * @param BufferInterface $buffer
     * @param int|string $state
     * @return iterable|TokenInterface|null
     */
    protected function next(ReadableInterface $source, BufferInterface $buffer, $state)
    {
        $offset = $buffer->current()->getOffset();

        $result = parent::next($source, $buffer, $state);

        if ($result instanceof Node) {
            $result->offset = $offset;
            $result->file = $source;
        }

        return $result;
    }
}
