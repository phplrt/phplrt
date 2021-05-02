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
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Exception\Renderer\ConsoleRenderer;
use Phplrt\Position\Interval;
use Phplrt\Position\Position;
use Phplrt\Source\File;

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
     * RuntimeException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     * @deprecated This class is deprecated since 4.0. Please use {@see RendererInterface} implementation instead.
     */
    public function getOriginalMessage(): string
    {
        return $this->message;
    }

    /**
     * @return ReadableInterface
     */
    public function getSource(): ReadableInterface
    {
        if ($this->source === null) {
            if (\is_file($this->getFile())) {
                return File::fromPathname($this->getFile());
            }

            return File::empty();
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
    }

    /**
     * @return TokenInterface
     */
    public function getToken(): TokenInterface
    {
        if ($this->token === null) {
            $position = Position::fromLineAndColumn($this->getSource(), $this->getLine());

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
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $renderer = new ConsoleRenderer();

        $token = $this->getToken();
        $source = $this->getSource();

        $interval = new Interval(
            Position::fromOffset($source, $token->getOffset()),
            Position::fromOffset($source, $token->getOffset() + $token->getBytes())
        );

        return $renderer->renderIn($this, $source, $interval);
    }
}
