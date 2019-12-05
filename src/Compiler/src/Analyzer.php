<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler;

use Phplrt\Grammar\Lexeme;
use Phplrt\Visitor\Visitor;
use Phplrt\Grammar\Optional;
use Phplrt\Grammar\Repetition;
use Phplrt\Grammar\Alternation;
use Phplrt\Grammar\Concatenation;
use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Compiler\Ast\Def\PragmaDef;
use Phplrt\Compiler\Ast\Stmt\RuleStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;
use Phplrt\Compiler\Ast\Stmt\TokenStmt;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Contracts\Grammar\RuleInterface;
use Phplrt\Compiler\Ast\Stmt\RepetitionStmt;
use Phplrt\Compiler\Ast\Stmt\AlternationStmt;
use Phplrt\Compiler\Ast\Stmt\ConcatenationStmt;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Source\Exception\NotAccessibleException;

/**
 * Class Analyzer
 */
class Analyzer extends Visitor
{
    /**
     * @var string
     */
    public const STATE_DEFAULT = 'default';

    /**
     * @var string
     */
    public const PRAGMA_ROOT = 'root';

    /**
     * @var array|RuleInterface
     */
    public $rules = [];

    /**
     * @var array|string[]
     */
    public $reducers = [];

    /**
     * @var array|string[]
     */
    public $tokens = [
        self::STATE_DEFAULT => [],
    ];

    /**
     * @var array|string[]
     */
    public $transitions = [];

    /**
     * @var array|string[]
     */
    public $skip = [];

    /**
     * @var string|int|null
     */
    public $initial;

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var IdCollection
     */
    private $ids;

    /**
     * ParserBuilder constructor.
     *
     * @param IdCollection $ids
     */
    public function __construct(IdCollection $ids)
    {
        $this->ids = $ids;
    }

    /**
     * @param NodeInterface $node
     * @return mixed|void|null
     */
    public function enter(NodeInterface $node)
    {
        if ($node instanceof TokenDef) {
            $state = $node->state ?: self::STATE_DEFAULT;

            if (! \array_key_exists($state, $this->tokens)) {
                $this->tokens[$state] = [];
            }

            $this->tokens[$state][$node->name] = $node->value;

            if ($node->next) {
                $this->transitions[$state][$node->name] = $node->next;
            }

            if (! $node->keep) {
                $this->skip[] = $node->name;
            }
        }

        if ($node instanceof PatternStmt) {
            $lexemes = \array_reverse($this->tokens[self::STATE_DEFAULT]);
            $lexemes[$node->name] = $node->pattern;

            $this->tokens[self::STATE_DEFAULT] = \array_reverse($lexemes);
        }
    }

    /**
     * @param NodeInterface $node
     * @return mixed|void|null
     * @throws ParserRuntimeException
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function leave(NodeInterface $node)
    {
        if ($node instanceof PragmaDef) {
            if ($node->name !== self::PRAGMA_ROOT) {
                $error = 'Unrecognized pragma "%s"';
                throw new GrammarException(\sprintf($error, $node->name), $node->file, $node->offset);
            }

            $this->initial = $this->name($node->value);
        }

        if ($node instanceof RuleDef) {
            $id = $this->register($this->rule($node), $node->name);

            if ($node->delegate->code !== null) {
                $this->reducers[$id] = $node->delegate->code;
            }
        }
    }

    /**
     * @param string $rule
     * @return string|int
     */
    private function name(string $rule)
    {
        if ($this->ids->rule($rule) === false) {
            if (\array_key_exists($rule, $this->aliases)) {
                return $this->aliases[$rule];
            }

            return $this->aliases[$rule] = $this->counter++;
        }

        return $rule;
    }

    /**
     * @param RuleInterface $rule
     * @param string|null $name
     * @return string|int
     */
    private function register(RuleInterface $rule, string $name = null)
    {
        if ($name === null) {
            $this->rules[$this->counter] = $rule;

            \ksort($this->rules);

            return $this->counter++;
        }

        $id = $this->name($name);

        $this->rules[$id] = $rule;

        if ($this->initial === null) {
            $this->initial = $id;
        }

        return $id;
    }

    /**
     * @param RuleDef $def
     * @return RuleInterface
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function rule(RuleDef $def): RuleInterface
    {
        $rule = $this->reduce($def->body);

        return $rule instanceof RuleInterface ? $rule : new Concatenation([$rule]);
    }

    /**
     * @param Statement $statement
     * @return RuleInterface|string
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function reduce(Statement $statement)
    {
        switch (true) {
            case $statement instanceof AlternationStmt:
                return new Alternation($this->loadForAlternation($statement));

            case $statement instanceof RepetitionStmt:
                $info = $statement->quantifier;

                if ($info->from === 0 && $info->to === 1) {
                    return new Optional($this->load($statement->statement));
                }

                return new Repetition($this->load($statement->statement), $info->from, $info->to);

            case $statement instanceof ConcatenationStmt:
                return new Concatenation($this->load($statement->statements));

            case $statement instanceof PatternStmt:
                return new Lexeme($statement->name, false);

            case $statement instanceof TokenStmt:
                return $this->tokenRelation($statement);

            case $statement instanceof RuleStmt:
                return $this->ruleRelation($statement);

            default:
                $error = \sprintf('Unsupported statement %s', \class_basename($statement));

                throw new GrammarException($error, $statement->file, $statement->offset);
        }
    }

    /**
     * @param AlternationStmt $choice
     * @return array
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function loadForAlternation(AlternationStmt $choice): array
    {
        $choices = [];

        foreach ($choice->statements as $stmt) {
            $choices[] = $this->map($this->reduce($stmt));

            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach (\array_diff_assoc($choices, \array_unique($choices)) as $relation) {
                $error = 'The alternation (OR condition) contains excess repeating relation %s';
                throw new GrammarException(\sprintf($error, $relation), $stmt->file, $stmt->offset);
            }
        }

        return $choices;
    }

    /**
     * @param RuleInterface|string $rule
     * @return int|string
     */
    private function map($rule)
    {
        if ($rule instanceof RuleInterface) {
            return $this->register($rule);
        }

        return $rule;
    }

    /**
     * @param mixed $stmt
     * @return array|int|int[]|string|string[]
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function load($stmt)
    {
        if (\is_array($stmt)) {
            return $this->mapAll($this->reduceAll($stmt));
        }

        return $this->map($this->reduce($stmt));
    }

    /**
     * @param array $rules
     * @return array|int[]
     */
    private function mapAll(array $rules): array
    {
        $result = [];

        foreach ($rules as $rule) {
            $result[] = $this->map($rule);
        }

        return $result;
    }

    /**
     * @param array $statements
     * @return array
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function reduceAll(array $statements): array
    {
        $result = [];

        foreach ($statements as $stmt) {
            $result[] = $this->reduce($stmt);
        }

        return $result;
    }

    /**
     * @param TokenStmt $token
     * @return Lexeme
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    private function tokenRelation(TokenStmt $token): Lexeme
    {
        if ($this->ids->lexeme($token->name) === null) {
            $error = \sprintf('Token "%s" has not been defined', $token->name);

            throw new GrammarException($error, $token->file, $token->offset);
        }

        return new Lexeme($token->name, $token->keep);
    }

    /**
     * @param RuleStmt $rule
     * @return int|string
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    private function ruleRelation(RuleStmt $rule)
    {
        if ($this->ids->rule($rule->name) === null) {
            $error = \sprintf('Rule "%s" has not been defined', $rule->name);

            throw new GrammarException($error, $rule->file, $rule->offset);
        }

        return $this->name($rule->name);
    }
}
