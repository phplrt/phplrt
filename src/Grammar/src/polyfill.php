<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser\Rule {

    if (! \interface_exists(RuleInterface::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        interface RuleInterface extends \Phplrt\Contracts\Grammar\RuleInterface
        {
        }
    }

    if (! \interface_exists(TerminalInterface::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        interface TerminalInterface extends \Phplrt\Contracts\Grammar\TerminalInterface, RuleInterface
        {
        }
    }

    if (! \interface_exists(ProductionInterface::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        interface ProductionInterface extends \Phplrt\Contracts\Grammar\ProductionInterface, RuleInterface
        {
        }
    }

    if (! \class_exists(Rule::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        abstract class Rule extends \Phplrt\Grammar\Rule implements RuleInterface
        {
        }
    }

    if (! \class_exists(Production::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        abstract class Production extends \Phplrt\Grammar\Production implements ProductionInterface
        {
        }
    }

    if (! \class_exists(Terminal::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        abstract class Terminal extends \Phplrt\Grammar\Terminal implements TerminalInterface
        {
        }
    }

    if (! \class_exists(Alternation::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        class Alternation extends \Phplrt\Grammar\Alternation implements ProductionInterface
        {
        }
    }

    if (! \class_exists(Concatenation::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        class Concatenation extends \Phplrt\Grammar\Concatenation implements ProductionInterface
        {
        }
    }

    if (! \class_exists(Lexeme::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        class Lexeme extends \Phplrt\Grammar\Lexeme implements TerminalInterface
        {
        }
    }

    if (! \class_exists(Optional::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        class Optional extends \Phplrt\Grammar\Optional implements ProductionInterface
        {
        }
    }

    if (! \class_exists(Repetition::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        class Repetition extends \Phplrt\Grammar\Repetition implements ProductionInterface
        {
        }
    }
}
