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

}
namespace Phplrt\Parser\Buffer {

    if (! \interface_exists(BufferInterface::class)) {
        /**
         * @deprecated since version 2.3 and will be removed in 3.0.
         */
        interface BufferInterface extends \Phplrt\Contracts\Lexer\BufferInterface
        {
        }
    }

}
