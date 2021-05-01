<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Parser;

interface ParserConfigsInterface
{
    /**
     * The initial state (initial rule identifier) of the parser
     * configuration option key.
     *
     * @var string
     */
    public const CONFIG_INITIAL_RULE = 'initial';

    /**
     * An abstract syntax tree builder instance configuration option key.
     *
     * @var string
     */
    public const CONFIG_AST_BUILDER = 'builder';

    /**
     * Configuration option key for an implementation of tokens buffer factory.
     *
     * @var string
     */
    public const CONFIG_BUFFER = 'buffer';

    /**
     * Configuration option key for an implementation of tokens buffer size.
     *
     * @var string
     */
    public const CONFIG_BUFFER_SIZE = 'buffer_size';

    /**
     * Configuration option key for token indicating the end of parsing.
     *
     * @var string
     */
    public const CONFIG_EOI = 'eoi';

    /**
     * @var string
     */
    public const CONFIG_STEP_REDUCER = 'step';
}
