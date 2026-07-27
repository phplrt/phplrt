<?php

declare(strict_types=1);

namespace Phplrt\Parser\Grammar;

/**
 * A rule that is recognized against the input rather than by means of the
 * other rules of the grammar.
 *
 * Every reading ends at a terminal: the rules above it only decide in which
 * order and how many times the terminals below are tried.
 */
interface TerminalInterface extends RuleInterface {}
