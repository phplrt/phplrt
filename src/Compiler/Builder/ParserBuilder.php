<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Builder;

use Phplrt\Compiler\Ast\Def\RuleDef;
use Phplrt\Compiler\Ast\Stmt\AlternationStmt;
use Phplrt\Compiler\Ast\Stmt\ConcatenationStmt;
use Phplrt\Compiler\Ast\Stmt\PatternStmt;
use Phplrt\Compiler\Ast\Stmt\RepetitionStmt;
use Phplrt\Compiler\Ast\Stmt\RuleStmt;
use Phplrt\Compiler\Ast\Stmt\Statement;
use Phplrt\Compiler\Ast\Stmt\TokenStmt;
use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Parser\Exception\ParserRuntimeException;
use Phplrt\Parser\Parser;
use Phplrt\Parser\Rule\Alternation;
use Phplrt\Parser\Rule\Concatenation;
use Phplrt\Parser\Rule\Lexeme;
use Phplrt\Parser\Rule\Optional;
use Phplrt\Parser\Rule\Repetition;
use Phplrt\Parser\Rule\RuleInterface;
use Phplrt\Visitor\Visitor;

/**
 * Class LexerBuilder
 */
class ParserBuilder extends Visitor
{
    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var array|int[]
     */
    private $aliases = [];

    /**
     * @param NodeInterface $node
     * @return mixed|void|null
     * @throws ParserRuntimeException
     */
    public function enter(NodeInterface $node)
    {
        if ($node instanceof RuleDef) {
            $rule = $this->reduce($node->body);
            $rule = $rule instanceof RuleInterface ? $rule : new Concatenation([$rule]);

            $this->register($node, $rule);
        }
    }

    /**
     * @param Statement $statement
     * @return RuleInterface|string
     * @throws ParserRuntimeException
     */
    private function reduce(Statement $statement)
    {
        switch (true) {
            case $statement instanceof AlternationStmt:
                return new Alternation($this->load($statement->statements));

            case $statement instanceof RepetitionStmt:
                $info = $statement->quantifier;

                if ($info->from === 0 && $info->to === 1) {
                    return new Optional($this->load($statement->statement));
                }

                return new Repetition($this->load($statement->statement), $info->from, $info->to);

            case $statement instanceof ConcatenationStmt:
                return new Concatenation($this->load($statement->statements));

            case $statement instanceof TokenStmt:
                return new Lexeme($statement->name, $statement->keep);

            case $statement instanceof RuleStmt:
                return $statement->name;

            case $statement instanceof PatternStmt:
                return new Lexeme($statement->name, false);

            default:
                $error = \sprintf('Unsupported statement %s', \class_basename($statement));

                throw new ParserRuntimeException($error, $statement->getOffset(), $statement);
        }
    }

    /**
     * @param mixed $stmt
     * @return array|int|int[]|string|string[]
     * @throws ParserRuntimeException
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
     * @param RuleInterface|string $rule
     * @return int|string
     */
    private function map($rule)
    {
        if (\is_string($rule)) {
            return $rule;
        }

        $this->rules[] = $rule;

        return \count($this->rules) ? \array_key_last($this->rules) : 0;
    }

    /**
     * @param array $statements
     * @return array
     * @throws ParserRuntimeException
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
     * @param RuleDef $def
     * @param RuleInterface $rule
     * @return void
     */
    private function register(RuleDef $def, RuleInterface $rule): void
    {
        $this->rules[$def->name] = $rule;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param LexerInterface $lexer
     * @return ParserInterface
     */
    public function getParser(LexerInterface $lexer): ParserInterface
    {
        return new Parser($lexer, $this->rules);
    }
}
