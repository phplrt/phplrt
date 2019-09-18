<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Driver;

use Phplrt\Lexer\Exception\SourceTypeException;

/**
 * Class Driver
 */
abstract class Driver implements DriverInterface
{
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
     * @param int $offset
     * @return string|resource
     */
    protected function seek($source, int $offset = 0)
    {
        switch (true) {
            case \is_string($source):
                return $offset === 0 ? $source : \substr($source, $offset);

            case \is_resource($source):
                \fseek($source, $offset);
                return $source;

            default:
                throw new SourceTypeException($source);
        }
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
                throw new SourceTypeException($source);
        }
    }
}
