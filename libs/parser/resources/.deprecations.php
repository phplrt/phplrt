<?php

namespace Phplrt\Grammar {

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Terminal} instead.
     */
    abstract class Terminal extends \Phplrt\Parser\Grammar\Terminal
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Repetition} instead.
     */
    class Repetition extends \Phplrt\Parser\Grammar\Repetition
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Optional} instead.
     */
    class Optional extends \Phplrt\Parser\Grammar\Optional
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Production} instead.
     */
    abstract class Production extends \Phplrt\Parser\Grammar\Production
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Concatenation} instead.
     */
    class Concatenation extends \Phplrt\Parser\Grammar\Concatenation
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Builder} instead.
     */
    class Builder extends \Phplrt\Parser\Grammar\Builder
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Rule} instead.
     */
    class Rule extends \Phplrt\Parser\Grammar\Rule
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Lexeme} instead.
     */
    class Lexeme extends \Phplrt\Parser\Grammar\Lexeme
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\Alternation} instead.
     */
    class Alternation extends \Phplrt\Parser\Grammar\Alternation
    {
    }
}

namespace Phplrt\Contracts\Grammar {

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\ProductionInterface} instead.
     */
    interface ProductionInterface extends \Phplrt\Parser\Grammar\ProductionInterface
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\RuleInterface} instead.
     */
    interface RuleInterface extends \Phplrt\Parser\Grammar\RuleInterface
    {
    }

    /**
     * @deprecated since phplrt 3.2 and will be removed in 4.0, use {@see \Phplrt\Parser\Grammar\TerminalInterface} instead.
     */
    interface TerminalInterface extends \Phplrt\Parser\Grammar\TerminalInterface
    {
    }
}
