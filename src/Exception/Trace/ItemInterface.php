<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Exception\Trace;

use Phplrt\Position\PositionInterface;

/**
 * Interface ItemInterface
 */
interface ItemInterface extends PositionInterface, Renderable
{
    /**
     * @return string
     */
    public function getFile(): string;

    /**
     * @inheritdoc
     */
    public function getLine(): int;

    /**
     * @inheritdoc
     */
    public function getColumn(): int;
}
