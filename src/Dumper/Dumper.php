<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Dumper;

use Phplrt\Contracts\Ast\NodeInterface;
use Phplrt\Contracts\Dumper\DumperInterface;

/**
 * Class Dumper
 */
class Dumper
{
    /**
     * @return DumperInterface
     */
    private static function resolve(): DumperInterface
    {
        if (\class_exists(\DOMDocument::class)) {
            return new XmlDumper();
        }

        return new HoaDumper();
    }

    /**
     * @param mixed|NodeInterface $node
     * @return string
     */
    public static function dump($node): string
    {
        return self::resolve()->dump($node);
    }
}
