<?php

declare(strict_types=1);

namespace Phplrt\Parser\Environment;

final class XdebugSelector implements SelectorInterface
{
    /**
     * A value that may be required for the parser to work.
     *
     * In the vast majority of cases, this restriction will
     * be sufficient.
     *
     * @var int<0, max>
     */
    public const DEFAULT_EXPECTED_RECURSION_DEPTH = 4096;

    /**
     * A value containing the current nesting depth state
     * defined by Xdebug extension.
     *
     * This value must first be set from the global environment
     * (PHP configuration) for its subsequent restoration after
     * the end of the parser.
     *
     * @readonly
     */
    private int $actualRecursionDepth;

    /**
     * The value contains {@see true} if the Xdebug extension
     * is available in the environment and controls the nesting
     * of the recursion depth.
     *
     * @readonly
     */
    private bool $enabled;

    /**
     * @param int<0, max> $expectedRecursionDepth
     */
    public function __construct(
        /**
         * @readonly
         */
        private int $expectedRecursionDepth = self::DEFAULT_EXPECTED_RECURSION_DEPTH
    ) {
        $this->enabled = \extension_loaded('xdebug');
        $this->actualRecursionDepth = (int) \ini_get('xdebug.max_nesting_level');
    }

    /**
     * Disables Xdebug restrictions for the {@see Parser} to work.
     */
    public function prepare(): void
    {
        if ($this->enabled) {
            \ini_set('xdebug.max_nesting_level', (string) $this->expectedRecursionDepth);
        }
    }

    /**
     * Resets all Xdebug settings/restrictions to default.
     */
    public function rollback(): void
    {
        if ($this->enabled) {
            \ini_set('xdebug.max_nesting_level', (string) $this->actualRecursionDepth);
        }
    }
}
