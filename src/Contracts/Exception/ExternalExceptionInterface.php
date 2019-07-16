<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Exception;

use Phplrt\Contracts\Io\Readable;
use Phplrt\Contracts\Position\PositionInterface;

/**
 * Interface ExternalExceptionInterface
 */
interface ExternalExceptionInterface extends \Throwable, PositionInterface
{
    /**
     * @return \Phplrt\Contracts\Io\Readable|null
     */
    public function getReadable(): ?Readable;
}
