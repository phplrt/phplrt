<?php

declare(strict_types=1);

namespace Phplrt\Parser\Environment;

final class Factory implements SelectorInterface
{
    /**
     * @var list<SelectorInterface>
     * @readonly
     */
    private array $selectors;

    /**
     * Factory "prepared" state.
     */
    private bool $isPrepared = false;

    /**
     * @param list<SelectorInterface>|null $selectors
     */
    public function __construct(?array $selectors = null)
    {
        $this->selectors = $selectors ?? $this->getDefaultSelectors();
    }

    /**
     * @return list<SelectorInterface>
     */
    private function getDefaultSelectors(): array
    {
        return [
            new XdebugSelector(),
        ];
    }

    public function prepare(): void
    {
        if ($this->isPrepared === true) {
            return;
        }

        $this->isPrepared = true;

        foreach ($this->selectors as $handler) {
            $handler->prepare();
        }
    }

    public function rollback(): void
    {
        if ($this->isPrepared === false) {
            return;
        }

        foreach ($this->selectors as $handler) {
            $handler->rollback();
        }

        $this->isPrepared = false;
    }
}
