<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Renderer;

use Laminas\Code\Generator\ValueGenerator;

class LaminasRenderer extends Renderer
{
    public function fromPhp($data, int $depth = 0, bool $multiline = true): string
    {
        $generator = new ValueGenerator($data, ValueGenerator::TYPE_AUTO, $this->getMultilineOption($multiline));
        $generator->setArrayDepth($depth);

        return \rtrim($generator->generate());
    }

    /**
     * @param bool $multiline
     * @return ValueGenerator::OUTPUT_*
     */
    private function getMultilineOption(bool $multiline): string
    {
        if ($multiline) {
            return ValueGenerator::OUTPUT_MULTIPLE_LINE;
        }

        return ValueGenerator::OUTPUT_SINGLE_LINE;
    }
}
