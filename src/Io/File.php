<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Io;

/**
 * Class File
 */
final class File implements FactoryInterface
{
    use FactoryTrait;

    /**
     * File constructor.
     */
    private function __construct()
    {
        throw new \LogicException('File factory is not instantiable');
    }
}
