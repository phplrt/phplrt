<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Exception\FactoryInterface;
use Phplrt\Contracts\Exception\MutableExceptionInterface;
use Phplrt\Contracts\Io\Readable;
use Phplrt\Exception\MutableException\MutableExceptionTrait;
use Phplrt\Io\File;

/**
 * Class ExternalException
 */
class ExternalException extends \Exception implements
    FactoryInterface,
    MutableExceptionInterface
{
    use FactoryTrait;
    use MutableExceptionTrait;

    /**
     * @var \Phplrt\Contracts\Io\Readable|null
     */
    protected $readable;

    /**
     * @param string $msg
     * @param int $code
     * @param \Throwable|null $prev
     * @return MutableExceptionInterface
     */
    protected static function create(string $msg, int $code = 0, \Throwable $prev = null): MutableExceptionInterface
    {
        return new static($msg, $code, $prev);
    }

    /**
     * @return \Phplrt\Contracts\Io\Readable|null
     */
    public function getReadable(): ?Readable
    {
        if ($this->readable === null) {
            return \is_file($this->getFile()) ? File::fromPathname($this->getFile()) : null;
        }

        return $this->readable;
    }
}
