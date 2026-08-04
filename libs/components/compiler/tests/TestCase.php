<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Node\Declaration;
use Phplrt\Compiler\Node\Node;
use Phplrt\Compiler\Node\Reducer;
use Phplrt\Compiler\Node\Statement;
use Phplrt\Compiler\Syntax\PP2\PP2Parser;
use Phplrt\Source\Source;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return list<string>
     */
    protected static function describe(string $source): array
    {
        $result = [];

        foreach (new PP2Parser()->parse(new Source($source)) as $declaration) {
            $result[] = self::describeNode($declaration);
        }

        return $result;
    }

    protected static function describeNode(Node $node): string
    {
        return match (true) {
            $node instanceof Declaration\TokenDeclaration => \vsprintf('%s %s%s %s%s', [
                $node->isHidden ? '%skip' : '%token',
                $node->state === null ? '' : $node->state . ':',
                $node->name,
                $node->pattern,
                $node->next === null ? '' : ' -> ' . $node->next,
            ]),
            $node instanceof Declaration\PragmaDeclaration => \sprintf(
                '%%pragma %s %s',
                $node->name,
                $node->value,
            ),
            $node instanceof Declaration\IncludeDeclaration => \sprintf('%%include %s', $node->target),
            $node instanceof Declaration\RuleDeclaration => \vsprintf('%s%s%s : %s ;', [
                $node->isKept ? '#' : '',
                $node->name,
                $node->reducer === null ? '' : ' ' . self::describeNode($node->reducer),
                self::describeNode($node->body),
            ]),
            $node instanceof Reducer\CodeReducer => \sprintf('-> {%s}', $node->code),
            $node instanceof Reducer\ClassReducer => \sprintf('-> %s', $node->class),
            $node instanceof Statement\Alternation => self::describeChildren($node->statements, ' | '),
            $node instanceof Statement\Concatenation => self::describeChildren($node->statements, ' '),
            $node instanceof Statement\Repetition => self::describeNode($node->statement)
                . self::describeNode($node->quantifier),
            $node instanceof Statement\Quantifier => \sprintf(
                '{%d,%s}',
                $node->min,
                \is_infinite($node->max) ? '' : (string) $node->max,
            ),
            $node instanceof Statement\TokenReference => $node->isKept
                ? \sprintf('<%s>', $node->name)
                : \sprintf('::%s::', $node->name),
            $node instanceof Statement\RuleReference => \sprintf('%s()', $node->name),
            $node instanceof Statement\InlinePattern => \sprintf('"%s"', $node->pattern),
        };
    }

    /**
     * @param non-empty-list<Statement\Statement> $statements
     */
    private static function describeChildren(array $statements, string $delimiter): string
    {
        $result = [];

        foreach ($statements as $statement) {
            $result[] = self::describeNode($statement);
        }

        return \sprintf('(%s)', \implode($delimiter, $result));
    }
}
