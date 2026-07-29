<?php

declare(strict_types=1);

namespace Phplrt\Parser\Internal\Tracing;

use Phplrt\Parser\Grammar\RuleInterface;

/**
 * Everything about a grammar that is known before the recognition starts.
 *
 * The rules are written down as they are, while what may be told about them
 * beforehand is calculated once and read off a table during the recognition,
 * so the very same grammar is recognized the same way no matter how much of
 * this is known.
 *
 * @internal this is an internal library class, please do not use it in your code
 * @psalm-internal Phplrt\Parser
 */
final readonly class GrammarTable
{
    /**
     * The identifiers of the tokens a rule may begin with, indexed by the rule
     * identifiers, or {@see null} in case of the rule admits any token at all.
     *
     * A rule that may be recognized without consuming a token is admitted
     * whatever comes next, and so is every rule of a grammar the lookahead of
     * which is unknown. Both are written down the same way, because both mean
     * the very same thing: there is nothing this rule may be rejected by.
     *
     * @var array<int, array<int, true>|null>
     */
    public array $lookahead;

    /**
     * The rules that become a node of the result, indexed by the rule
     * identifiers.
     *
     * A rule that is present builds its own value from the values of its
     * children and passes it to its reducer. A rule that is absent gives the
     * values of its children to the rule above, so it leaves nothing in the
     * result.
     *
     * @var array<int, bool>
     */
    public array $presentInTree;

    /**
     * @param list<RuleInterface> $rules
     * @param int<0, max> $initial
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @param array<int, bool> $presentInTree
     */
    public function __construct(
        /**
         * @var list<RuleInterface>
         */
        public array $rules,
        /**
         * The identifier of the rule the recognition starts at.
         *
         * @var int<0, max>
         */
        public int $initial,
        array $startTokens = [],
        array $matchesEmptyInput = [],
        array $presentInTree = [],
    ) {
        $this->lookahead = self::calculateLookahead($rules, $startTokens, $matchesEmptyInput);

        // A rule the caller says nothing about is important, so an unknown
        // table admits every rule of the grammar
        $this->presentInTree = $presentInTree === []
            ? \array_fill_keys(\array_keys($rules), true)
            : $presentInTree;
    }

    /**
     * @param list<RuleInterface> $rules
     * @param array<int, array<int, true>> $startTokens
     * @param array<int, bool> $matchesEmptyInput
     * @return array<int, array<int, true>|null>
     */
    private static function calculateLookahead(array $rules, array $startTokens, array $matchesEmptyInput): array
    {
        /**
         * Both tables are needed to reject a rule, so one without the other
         * admits everything the same way as no table at all does.
         *
         * A grammar recognized this way is a plain PEG: it reads the very same
         * sources, only slower, and reports what is wrong at a later stage (in
         * a more nested rule), because the rules alone say nothing about where
         * the reading was supposed to go.
         */
        if ($startTokens === [] || $matchesEmptyInput === []) {
            return \array_fill_keys(\array_keys($rules), null);
        }

        $result = [];

        foreach ($rules as $rule => $_) {
            $result[$rule] = ($matchesEmptyInput[$rule] ?? true)
                ? null
                : ($startTokens[$rule] ?? []);
        }

        return $result;
    }
}
