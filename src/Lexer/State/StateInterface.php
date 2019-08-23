<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Lexer\State;

use Phplrt\Contracts\Lexer\TokenInterface;

/**
 * Interface StateInterface
 */
interface StateInterface
{
    /**
     * @param string $source
     * @param int $offset
     * @return \Generator|TokenInterface[]
     */
    public function execute(string $source, int $offset): \Generator;
}
