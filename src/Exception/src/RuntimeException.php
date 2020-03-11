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
     * @var string
     */
    private string $original;

    /**
     * RuntimeException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->original = $message;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getOriginalMessage(): string
    {
        return $this->original;
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
     * @param ReadableInterface|null $source
     * @return void
     */
    public function setSource(?ReadableInterface $source): void
    {
        $this->source = $source;

        $this->sync();
    }

    /**
     * @return void
     */
    protected function sync(): void
    {
        $file = $this->getSource();

        if ($file instanceof FileInterface && $this->token) {
            $this->file = $file->getPathname();
            $this->line = $this->getPosition()->getLine();
        }

        if ($this->source && $this->token) {
            $this->message = $this->original . $this->getMessageSuffix($this->source, $this->token);
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
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        if ($this->token === null) {
            $position = Position::fromPosition($this->getSource(), $this->getLine());

            return new UndefinedToken($position);
        }

        return $this->token;
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
     * @param ReadableInterface $src
     * @param TokenInterface $token
     * @return string
     */
    private function getMessageSuffix(ReadableInterface $src, TokenInterface $token): string
    {
        $renderer = new ErrorInformationRenderer($src, $token);

        return \PHP_EOL . $renderer->render();
    }
}
