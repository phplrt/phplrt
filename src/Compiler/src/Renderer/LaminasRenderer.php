<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Compiler\Renderer;

use Laminas\Code\Generator\ValueGenerator;

/**
 * Class LaminasRenderer
 */
class LaminasRenderer extends Renderer
{
    /**
     * {@inheritDoc}
     */
    public function fromPhp($data, int $depth = 0, bool $multiline = true): string
    {
        $generator = new ValueGenerator($data, ValueGenerator::TYPE_AUTO, $this->getMultilineOption($multiline));
        $generator->setArrayDepth($depth);

        return \rtrim($generator->generate());
    }

    /**
     * @param bool $multiline
     * @return string
     */
    private function getMultilineOption(bool $multiline): string
    {
        return $multiline ? ValueGenerator::OUTPUT_MULTIPLE_LINE : ValueGenerator::OUTPUT_SINGLE_LINE;
    }
}
