<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Exception;

use Phplrt\Position\Position;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Source\Exception\NotAccessibleException;

class GrammarException extends \LogicException
{
    /**
     * @param int<0, max> $offset
     * @throws NotAccessibleException
     * @throws \RuntimeException
     */
    public function __construct(string $message, ReadableInterface $source, int $offset)
    {
        parent::__construct($message);

        $this->file = $source instanceof FileInterface ? $source->getPathname() : '{source}';
        $this->line = Position::fromOffset($source, $offset)->getLine();
    }
}
