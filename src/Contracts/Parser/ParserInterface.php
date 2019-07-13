<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Contracts\Parser;

use Phplrt\Contracts\Ast\RuleInterface;
use Phplrt\Contracts\Io\Readable;

/**
 * Interface ParserInterface
 */
interface ParserInterface
{
    /**
     * @param Readable $input
     * @return RuleInterface|mixed
     */
    public function parse(Readable $input);
}
