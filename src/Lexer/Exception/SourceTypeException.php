<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\Exception;

/**
 * Class SourceTypeException
 */
class SourceTypeException extends \TypeError
{
    /**
     * @var string
     */
    private const ERROR_ARGUMENT_TYPE = 'An input source argument should be a resource or string type, but %s given';

    /**
     * SourceTypeException constructor.
     *
     * @param $source
     * @param \Throwable|null $previous
     */
    public function __construct($source, \Throwable $previous = null)
    {
        parent::__construct(\sprintf(self::ERROR_ARGUMENT_TYPE, \gettype($source)), 0, $previous);
    }
}
