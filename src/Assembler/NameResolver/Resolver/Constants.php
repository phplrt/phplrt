<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Assembler\NameResolver\Resolver;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\String_;
use Phplrt\Assembler\Context\Aliases;
use Phplrt\Assembler\Context\ContextInterface;
use Phplrt\Assembler\Exception\DependencyException;

/**
 * Class Constants
 */
class Constants extends Resolver
{
    /**
     * @var string
     */
    private const CONST_CATEGORY_USER_DEFINED = 'user';

    /**
     * @var array|mixed[]
     */
    private static $constants;

    /**
     * Constants constructor.
     *
     * @param ContextInterface $context
     */
    public function __construct(ContextInterface $context)
    {
        parent::__construct($context);

        $this->bootConstantsIfNotBooted();
    }

    /**
     * @return void
     */
    private function bootConstantsIfNotBooted(): void
    {
        if (self::$constants === null) {
            self::$constants = \get_defined_constants(true)[self::CONST_CATEGORY_USER_DEFINED] ?? [];
        }
    }

    /**
     * @param Name $name
     * @param \Closure $export
     * @return mixed
     */
    public function resolve(Name $name, \Closure $export)
    {
        $const = $this->lookup(Aliases::TYPE_CONST, $name, static function (string $const): bool {
            return \defined($const);
        });

        if ($const === null) {
            return null;
        }

        return $this->isUserDefined($const) ? $this->import($const) : $name;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function isUserDefined(string $name): bool
    {
        return isset(self::$constants[$name]) || \array_key_exists($name, self::$constants);
    }

    /**
     * @param string $const
     * @return Node
     */
    private function import(string $const): Node
    {
        $value = self::$constants[$const];

        switch (true) {
            case \is_int($value):
                return new LNumber($value);

            case \is_float($value):
                return new DNumber($value);

            case \is_bool($value):
                return new Name($value === true ? 'true' : 'false');

            case $value === null:
                return new Name('null');

            case \is_string($value):
                return new String_($value);
        }

        $error = 'Can not assembly the constant %s which was defined by the %s type';
        throw new DependencyException(\sprintf($error, $const, \gettype($value)));
    }
}
