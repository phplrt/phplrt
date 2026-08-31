<?php

declare(strict_types=1);

namespace Phplrt\Contracts\Lexer;

/**
 * A tag a token or a group of tokens is marked with, so that these tokens can
 * be told apart from the rest of a stream.
 *
 * All properties described below SHOULD BE considered actual interface
 * requirements. Their absence in the code is due to support requirements
 * for PHP versions prior to 8.4.
 *
 * @property-read non-empty-string $name The name of the channel.
 *
 * @readonly
 */
interface ChannelInterface {}
