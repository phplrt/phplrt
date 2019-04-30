<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\MutableException;

/**
 * Trait MutableMessageTrait
 *
 * @mixin MutableMessageInterface
 * @mixin \Exception
 */
trait MutableMessageTrait
{
    /**
     * @param string $message
     * @param mixed ...$args
     * @return MutableMessageInterface|$this
     */
    public function withMessage(string $message, ...$args): MutableMessageInterface
    {
        $this->message = \vsprintf($message, $args);

        return $this;
    }
}
