<?php

declare(strict_types=1);

namespace Phplrt\Parser\Builder\Compiler;

use Phplrt\Lexer\Builder\LexerBuilderResult;
use Phplrt\Parser\Builder\Definition\AlternationRuleDefinition;
use Phplrt\Parser\Builder\Definition\ConcatenationRuleDefinition;
use Phplrt\Parser\Builder\Definition\OptionalRuleDefinition;
use Phplrt\Parser\Builder\Definition\RepetitionRuleDefinition;
use Phplrt\Parser\Builder\Definition\RuleDefinition;
use Phplrt\Parser\Builder\Definition\TerminalRuleDefinition;
use Phplrt\Parser\Builder\Exception\CompilationFailedException;

/**
 * Checks that every message the grammar carries may be reported at all.
 *
 * A message describes a rule that has been entered and could not be finished,
 * so a rule that cannot fail where it is written describes a failure that never
 * happens: the message is written, compiled and never read out.
 */
final readonly class UnreportableMessageParserCompilerPass implements
    ParserCompilerPassInterface
{
    /**
     * Said about a rule that is recognized whatever the input is.
     *
     * @var non-empty-string
     */
    private const string REASON_ALWAYS_MATCHES = 'the rule is recognized even when '
        . 'the input does not match it';

    /**
     * Said about a terminal the rule containing it may only be entered on.
     *
     * @var non-empty-string
     */
    private const string REASON_GUARDED = 'the rule containing it is rejected by this '
        . 'very token before it is entered';

    /**
     * Said about a rule written where a failure is not an error.
     *
     * @var non-empty-string
     */
    private const string REASON_NOT_REPORTED = 'nothing reports the failure of a rule '
        . 'written in this place';

    /**
     * @throws CompilationFailedException
     */
    public function process(ParserBuildingContext $context, LexerBuilderResult $lexer): void
    {
        $pending = self::findMessagedRules($context);

        if ($pending->count() === 0) {
            return;
        }

        $nullable = NullableRules::createFromRules($context->rules);

        /** @var \SplObjectStorage<RuleDefinition, non-empty-string> $reasons */
        $reasons = new \SplObjectStorage();

        foreach ($context->rules as $parent) {
            if ($parent instanceof ConcatenationRuleDefinition) {
                foreach ($parent->rules as $index => $rule) {
                    self::inspect($rule, $index === 0, $pending, $reasons);
                }

                continue;
            }

            /**
             * An alternative is only tried on the tokens the alternation may
             * begin with, unless the alternation reads an empty input: there is
             * no token such an alternation is rejected by, so every alternative
             * it has is tried wherever it stands.
             */
            if ($parent instanceof AlternationRuleDefinition) {
                $guarded = !$nullable->isNullable($parent);

                foreach ($parent->rules as $rule) {
                    self::inspect($rule, $guarded, $pending, $reasons);
                }
            }
        }

        foreach ($pending as $rule) {
            throw CompilationFailedException::becauseMessageCannotBeReported(
                rule: $rule,
                reason: $reasons[$rule] ?? self::REASON_NOT_REPORTED,
            );
        }
    }

    /**
     * Returns the rules carrying a message the compilation has to answer for.
     *
     * The rule the analysis starts at is contained by nothing, so its message
     * describes the source as a whole and is reported wherever the reading has
     * stopped.
     *
     * @return \SplObjectStorage<RuleDefinition, null>
     */
    private static function findMessagedRules(ParserBuildingContext $context): \SplObjectStorage
    {
        /** @var \SplObjectStorage<RuleDefinition, null> $result */
        $result = new \SplObjectStorage();

        foreach ($context->rules as $rule) {
            if ($rule->message !== null && $rule !== $context->initial) {
                $result->offsetSet($rule);
            }
        }

        return $result;
    }

    /**
     * Answers for the single place the given rule is written at.
     *
     * A rule is written in several places at once, so the first place that
     * reports it is the answer and the rule is asked about no more.
     *
     * @param \SplObjectStorage<RuleDefinition, null> $pending
     * @param \SplObjectStorage<RuleDefinition, non-empty-string> $reasons
     */
    private static function inspect(
        RuleDefinition $rule,
        bool $guarded,
        \SplObjectStorage $pending,
        \SplObjectStorage $reasons,
    ): void {
        if (!$pending->offsetExists($rule)) {
            return;
        }

        $reason = self::findReason($rule, $guarded);

        if ($reason === null) {
            $pending->offsetUnset($rule);

            return;
        }

        if (!$reasons->offsetExists($rule)) {
            $reasons->offsetSet($rule, $reason);
        }
    }

    /**
     * Returns why the given rule cannot fail where it is written, or
     * {@see null} in case of it can.
     *
     * @param bool $guarded whether the rule is only reached on the tokens it
     *        may begin with
     * @return non-empty-string|null
     */
    private static function findReason(RuleDefinition $rule, bool $guarded): ?string
    {
        if ($rule instanceof OptionalRuleDefinition) {
            return self::REASON_ALWAYS_MATCHES;
        }

        if ($rule instanceof RepetitionRuleDefinition && $rule->min === 0) {
            return self::REASON_ALWAYS_MATCHES;
        }

        /**
         * A terminal is rejected by the token itself rather than by the table,
         * so the tokens the rule containing it may begin with are its own and
         * reaching it is already recognizing it.
         */
        if ($guarded && $rule instanceof TerminalRuleDefinition) {
            return self::REASON_GUARDED;
        }

        return null;
    }
}
