<?php

declare(strict_types=1);

namespace Phplrt\Parser;

interface ParserConfigsInterface
{
    /**
     * The initial state (initial rule identifier) of the parser
     * configuration option key.
     *
     * @var non-empty-string
     */
    public const CONFIG_INITIAL_RULE = 'initial';

    /**
     * An abstract syntax tree builder instance configuration option key.
     *
     * @var non-empty-string
     */
    public const CONFIG_AST_BUILDER = 'builder';

    /**
     * Configuration option key for an implementation of tokens buffer
     * (subclass of \Phplrt\Contracts\Lexer\BufferInterface).
     *
     * @var non-empty-string
     */
    public const CONFIG_BUFFER = 'buffer';

    /**
     * Configuration option key for token indicating the end of parsing.
     *
     * @var non-empty-string
     */
    public const CONFIG_EOI = 'eoi';

    /**
     * @var non-empty-string
     */
    public const CONFIG_STEP_REDUCER = 'step';

    /**
     * @var non-empty-string
     */
    public const CONFIG_ALLOW_TRAILING_TOKENS = 'configAllowTrailingTokens';
}
