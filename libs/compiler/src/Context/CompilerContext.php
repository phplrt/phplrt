<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Context;

use Phplrt\Compiler\Ast\Def\PragmaDef;
use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Def\TokenDef;
use Phplrt\Compiler\Ast\Stmt\AlternationStmt;
use Phplrt\Compiler\Ast\Stmt\ConcatenationStmt;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Compiler\Ast\Stmt\RepetitionStmt;
use Phplrt\Compiler\Ast\Stmt\RuleStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;
use Phplrt\Compiler\Ast\Stmt\TokenStmt;
use Phplrt\Compiler\Exception\GrammarException;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Grammar\Alternation;
use Phplrt\Parser\Grammar\Concatenation;
use Phplrt\Parser\Grammar\Lexeme;
use Phplrt\Parser\Grammar\Optional;
use Phplrt\Parser\Grammar\Repetition;
use Phplrt\Parser\Grammar\RuleInterface;
use Phplrt\Source\Exception\NotAccessibleException;
use Phplrt\Visitor\Visitor;

/**
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Compiler
 */
class CompilerContext extends Visitor
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
     * @var array<RuleInterface>
     */
    public array $rules = [];

    /**
     * @var array<string>
     */
    public array $reducers = [];

    /**
     * @var array<non-empty-string, array<non-empty-string, non-empty-string>>
     */
    public array $tokens = [
        self::STATE_DEFAULT => [],
    ];

    /**
     * @var array<non-empty-string, array<non-empty-string, non-empty-string>>
     */
    public array $transitions = [];

    /**
     * @var array<array-key, non-empty-string>
     */
    public array $skip = [];

    /**
     * @var non-empty-string|int|null
     */
    public $initial;

    /**
     * @var int<0, max>
     */
    private int $counter = 0;

    /**
     * @var array<non-empty-string, int<0, max>>
     */
    private array $aliases = [];

    public function __construct(private IdCollection $ids) {}

    /**
     * @psalm-suppress PropertyTypeCoercion
     */
    public function enter(NodeInterface $node): void
    {
        if ($node instanceof TokenDef) {
            $state = $node->state ?? self::STATE_DEFAULT;

            if (!\array_key_exists($state, $this->tokens)) {
                $this->tokens[$state] = [];
            }

            $this->tokens[$state][$node->name] = $node->value;

            if ($node->next) {
                $this->transitions[$state][$node->name] = $node->next;
            }

            if (!$node->keep) {
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
     * @psalm-suppress PropertyTypeCoercion
     */
    public function leave(NodeInterface $node): void
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
     * @param non-empty-string $rule
     *
     * @return non-empty-string|int<0, max>
     */
    private function name(string $rule)
    {
        if ($this->ids->rule($rule) === false) {
            return $this->aliases[$rule] ??= $this->counter++;
        }

        return $rule;
    }

    /**
     * @param non-empty-string|null $name
     *
     * @return non-empty-string|int<0, max>
     */
    private function register(RuleInterface $rule, ?string $name = null)
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
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function rule(RuleDef $def): RuleInterface
    {
        $rule = $this->reduce($def->body);

        if ($rule instanceof RuleInterface) {
            return $rule;
        }

        return new Concatenation([$rule]);
    }

    /**
     * @return RuleInterface|non-empty-string|int<0, max>
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     *
     * @psalm-suppress PossiblyInvalidArgument
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
                $error = \sprintf('Unsupported statement %s', $statement::class);

                throw new GrammarException($error, $statement->file, $statement->offset);
        }
    }

    /**
     * @return array<RuleInterface|non-empty-string|int<0, max>>
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    private function loadForAlternation(AlternationStmt $choice): array
    {
        $choices = [];

        foreach ($choice->statements as $stmt) {
            $choices[] = $this->map($this->reduce($stmt));

            /** @var string $relation */
            foreach (\array_diff_assoc($choices, \array_unique($choices)) as $relation) {
                $error = 'The alternation (OR condition) contains excess repeating relation %s';
                throw new GrammarException(\sprintf($error, $relation), $stmt->file, $stmt->offset);
            }
        }

        return $choices;
    }

    /**
     * @param RuleInterface|non-empty-string|int<0, max> $rule
     *
     * @return RuleInterface|non-empty-string|int<0, max>
     */
    private function map(mixed $rule)
    {
        if ($rule instanceof RuleInterface) {
            return $this->register($rule);
        }

        return $rule;
    }

    /**
     * @param Statement|array<Statement> $stmt
     *
     * @return RuleInterface|non-empty-string|int<0, max>|array<RuleInterface|non-empty-string|int<0, max>>
     * @throws NotAccessibleException
     * @throws ParserRuntimeException
     * @throws \RuntimeException
     */
    private function load(mixed $stmt): mixed
    {
        if (\is_array($stmt)) {
            return $this->mapAll($this->reduceAll($stmt));
        }

        return $this->map($this->reduce($stmt));
    }

    /**
     * @param array<RuleInterface|non-empty-string|int<0, max>> $rules
     *
     * @return array<RuleInterface|non-empty-string|int<0, max>>
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
     * @param array<Statement> $statements
     *
     * @return array<RuleInterface|non-empty-string|int<0, max>>
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
     * @return non-empty-string|int<0, max>
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
