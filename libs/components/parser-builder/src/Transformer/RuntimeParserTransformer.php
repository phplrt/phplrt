<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Transformer;

use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Parser\Builder\Definition\Reducer\CallableReducer;
use Phplrt\Parser\Builder\Definition\Reducer\PhpCodeReducer;
use Phplrt\Parser\Builder\Definition\Reducer\ReducerInterface;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Exception\ParserCompilerException;
use Phplrt\Parser\Builder\ParserBuilderResult;
use Phplrt\Parser\Context;
use Phplrt\Parser\Parser;

/**
 * Turns the result of the compilation into the parser reading the input.
 *
 * @phpstan-import-type ReducerType from RuleDefinition
 */
final readonly class RuntimeParserTransformer
{
    /**
     * @throws ParserCompilerException in case of the grammar cannot be run
     */
    public function transform(ParserBuilderResult $result, LexerInterface $lexer): Parser
    {
        return new Parser(
            lexer: $lexer,
            grammar: $result->grammar,
            initial: $result->initial,
            reducers: $this->transformReducers($result),
            lookahead: $result->lookahead,
            presentInTree: $result->presentInTree,
            branchesByToken: $result->branchesByToken,
        );
    }

    /**
     * @return array<int<0, max>, ReducerType>
     * @throws ParserCompilerException
     */
    private function transformReducers(ParserBuilderResult $result): array
    {
        $reducers = [];

        foreach ($result->reducers as $rule => $reducer) {
            $reducers[$rule] = $this->transformReducer($reducer, $this->printRule($result, $rule));
        }

        return $reducers;
    }

    /**
     * @param non-empty-string $rule
     * @return ReducerType
     * @throws ParserCompilerException
     */
    private function transformReducer(ReducerInterface $reducer, string $rule): callable
    {
        return match (true) {
            $reducer instanceof CallableReducer => $reducer->callback,
            $reducer instanceof PhpCodeReducer => self::compileReducer($reducer, $rule),
        };
    }

    /**
     * Compiles the body of a reducer into the callback the parser calls.
     *
     * The body is written for a generated parser, where a reducer is a method,
     * so a body that refers to "$this" is compiled into a callback that can be
     * bound to an object and is unusable until it is.
     *
     * The compilation is done out of the object context, so that the callback
     * is left unbound instead of capturing the transformer itself.
     *
     * @param non-empty-string $rule
     * @throws ParserCompilerException
     */
    private static function compileReducer(PhpCodeReducer $reducer, string $rule): \Closure
    {
        $code = \vsprintf('return %sfunction (\\%s $ctx, mixed $children): mixed { %s };', [
            self::containsObjectReference($reducer->code) ? '' : 'static ',
            Context::class,
            $reducer->code,
        ]);

        try {
            $callback = eval($code);
        } catch (\ParseError $e) {
            throw ParserCompilerException::becauseReducerIsMalformed($rule, $e, $reducer->context);
        }

        \assert($callback instanceof \Closure, 'The compiled reducer is a callback');

        return $callback;
    }

    /**
     * Tells whether the given code refers to the object it belongs to.
     *
     * The code is read the way PHP reads it, so a "$this" written inside a
     * string or a comment is not mistaken for the variable.
     */
    private static function containsObjectReference(string $code): bool
    {
        foreach (\token_get_all('<?php ' . $code) as $token) {
            if (\is_array($token) && $token[0] === \T_VARIABLE && $token[1] === '$this') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return non-empty-string
     */
    private function printRule(ParserBuilderResult $result, int $rule): string
    {
        $name = \array_search($rule, $result->constants, true);

        if ($name === false) {
            return \sprintf('#%d', $rule);
        }

        return $name;
    }
}
