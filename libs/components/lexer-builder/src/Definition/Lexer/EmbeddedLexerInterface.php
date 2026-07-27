<?php

declare(strict_types=1);

namespace Phplrt\Lexer\Builder\Definition\Lexer;

/**
 * Reads a lexer state instead of the token definitions of that state.
 *
 * A lexer given as an instance can be run, but cannot be dumped into a
 * generated lexer, while a lexer given as code can be dumped, but has to be
 * compiled before it can be run. Which of them is required depends on what the
 * lexer is compiled for.
 *
 * @phpstan-sealed PhpCodeEmbeddedLexer|RuntimeEmbeddedLexer
 */
interface EmbeddedLexerInterface extends \Stringable {}
