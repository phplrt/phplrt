<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

/**
 * Class Driver
 */
abstract class Driver implements DriverInterface
{
    /**
     * @var string
     */
    private const ERROR_ARGUMENT_TYPE = 'A $source argument should be a resource or string type, but %s given';

    /**
     * @var array|string[]
     */
    protected $tokens;

    /**
     * State constructor.
     *
     * @param array|string[] $tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @param string|resource $source
     * @return string
     */
    protected function read($source): string
    {
        switch (true) {
            case \is_resource($source):
                return \stream_get_contents($source);

            case \is_string($source):
                return $source;

            default:
                throw new \TypeError(\sprintf(self::ERROR_ARGUMENT_TYPE, \gettype($source)));
        }
    }
}
