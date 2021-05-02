<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Grammar\PP2Grammar;

use Phplrt\Compiler\Ast\Def\PragmaDef;
use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Compiler\Ast\Expr\IncludeExpr;
use Phplrt\Compiler\Ast\Node;
use Phplrt\Compiler\Ast\Stmt\AlternationStmt;
use Phplrt\Compiler\Ast\Stmt\ClassDelegateStmt;
use Phplrt\Compiler\Ast\Stmt\ConcatenationStmt;
use Phplrt\Compiler\Ast\Stmt\DelegateStmt;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Compiler\Ast\Stmt\Quantifier;
use Phplrt\Compiler\Ast\Stmt\RepetitionStmt;
use Phplrt\Compiler\Ast\Stmt\RuleStmt;
use Phplrt\Compiler\Ast\Stmt\TokenStmt;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Lexer\Token\Composite;
use Phplrt\Parser\BuilderInterface;
use Phplrt\Parser\ContextInterface;

final class Builder implements BuilderInterface
{
    /**
     * @var array<\Closure(mixed): mixed>
     */
    private array $reducers;

    /**
     * Builder constructor.
     */
    public function __construct()
    {
        $this->reducers = $this->reducers();
    }

    /**
     * @return array<\Closure(mixed): mixed>
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
     * {@inheritDoc}
     */
    public function build(ContextInterface $context, $result)
    {
        $state = $context->getState();

        if (isset($this->reducers[$state])) {
            $result = $this->reducers[$state]($result);

            if ($result instanceof Node) {
                $token = $context->getToken();
                $result->offset = $token->getOffset();
                $result->file = $context->getSource();
            }

            return $result;
        }

        return null;
    }
}
