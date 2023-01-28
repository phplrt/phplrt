<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Ast\Def;

/**
 * @internal This is an internal class, please do not use it in your application code.
 * @psalm-internal Phplrt\Compiler
 * @psalm-suppress PropertyNotSetInConstructor
 */
class PragmaDef extends Definition
{
    /**
     * @var non-empty-string
     */
    public string $name;

    /**
     * @var non-empty-string
     */
    public string $value;

    /**
     * @param non-empty-string $name
     * @param non-empty-string $value
     */
    public function __construct(string $name, string $value)
    {
        assert($name !== '', 'Pragma name must not be empty');
        assert($name !== '', 'Name must not be empty');

        $this->name = $name;
        $this->value = $value;
    }
}
