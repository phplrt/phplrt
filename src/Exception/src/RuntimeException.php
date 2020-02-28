<?php

/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;
use Phplrt\Position\PositionInterface;
use Phplrt\Source\File;

/**
 * Class RuntimeException
 */
abstract class RuntimeException extends \RuntimeException implements RuntimeExceptionInterface
{
    /**
     * @var TokenInterface|null
     */
    private ?TokenInterface $token = null;

    /**
     * @var ReadableInterface|null
     */
    private ?ReadableInterface $source = null;

    /**
     * @param ReadableInterface|null $source
     * @return void
     */
    public function setSource(?ReadableInterface $source): void
    {
        $this->source = $source;

        $this->sync();
    }

    /**
     * @param TokenInterface|null $token
     * @return void
     */
    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;

        $this->sync();
    }

    /**
     * @return void
     */
    protected function sync(): void
    {
        $file = $this->getSource();

        if ($this->file instanceof FileInterface && $this->token) {
            $this->file = $file->getPathname();
            $this->line = $this->getPosition()->getLine();
        }
    }

    /**
     * @return PositionInterface
     */
    public function getPosition(): PositionInterface
    {
        return Position::fromOffset($this->getSource(), $this->getToken()->getOffset());
    }

    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface
    {
        if ($this->source === null) {
            return File::fromPathname($this->getFile());
        }

        return $this->source;
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        if ($this->token === null) {
            $position = Position::fromPosition($this->getSource(), $this->getLine());

            return new class($position) implements TokenInterface {
                /**
                 * @var PositionInterface
                 */
                private PositionInterface $position;

                /**
                 * @param PositionInterface $position
                 */
                public function __construct(PositionInterface $position)
                {
                    $this->position = $position;
                }

                /**
                 * {@inheritDoc}
                 */
                public function getName(): string
                {
                    return TokenInterface::END_OF_INPUT;
                }

                /**
                 * {@inheritDoc}
                 */
                public function getOffset(): int
                {
                    return $this->position->getOffset();
                }

                /**
                 * {@inheritDoc}
                 */
                public function getValue(): string
                {
                    return '';
                }

                /**
                 * {@inheritDoc}
                 */
                public function getBytes(): int
                {
                    return 0;
                }
            };
        }

        return $this->token;
    }
}
