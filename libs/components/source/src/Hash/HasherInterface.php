<?php

declare(strict_types=1);

namespace Phplrt\Source\Hash;

interface HasherInterface
{
    /**
     * @return non-empty-string
     */
    public function content(string $value): string;

    /**
     * @param non-empty-string $pathname
     *
     * @return non-empty-string
     */
    public function file(string $pathname): string;
}
