<?php
/**
 * This file is part of Phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Parser\Driver;

use Phplrt\Io\Readable;
use Phplrt\Ast\RuleInterface;
use Phplrt\Parser\ParserInterface;

/**
 * @deprecated use Phplrt\Parser\Parser instead.
 */
class Proxy implements ParserInterface
{
    /**
     * @var ParserInterface
     */
    private $parent;

    /**
     * Proxy constructor.
     *
     * @param ParserInterface $parent
     */
    public function __construct(ParserInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param Readable $input
     * @return RuleInterface|mixed
     */
    public function parse(Readable $input)
    {
        return $this->parent->parse($input);
    }

    /**
     * @param string $rule
     * @param \Closure $then
     * @return ParserInterface
     */
    public function extend(string $rule, \Closure $then): ParserInterface
    {
        return $this->parent->extend($rule, $then);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        return $this->parent->$name(...$arguments);
    }
}
