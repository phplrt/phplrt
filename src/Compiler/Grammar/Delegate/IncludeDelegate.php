<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler\Grammar\Delegate;

use Phplrt\Ast\Rule;
use Phplrt\Compiler\Exception\IncludeNotFoundException;
use Phplrt\Contracts\Io\Readable;
use Phplrt\Exception\ExternalException;
use Phplrt\Io\Exception\NotReadableException;
use Phplrt\Io\File;

/**
 * Class IncludeDelegate
 */
class IncludeDelegate extends Rule
{
    /**
     * @param Readable $from
     * @return Readable
     * @throws NotReadableException
     * @throws ExternalException
     */
    public function getPathname(Readable $from): Readable
    {
        $name = \trim(\substr($this->getChild(0)->getValue(), 9), '"\'');
        $dir  = \dirname($from->getPathname());

        foreach (['', '.pp', '.pp2'] as $ext) {
            $path = $dir . '/' . $name . $ext;

            if (\is_file($path)) {
                return File::fromPathname($path);
            }
        }

        $error = \sprintf('Can not find the grammar file "%s" in "%s"', $name, $dir);

        $exception = new IncludeNotFoundException($error);
        $exception->throwsIn($from, $this->getOffset());

        throw $exception;
    }
}
