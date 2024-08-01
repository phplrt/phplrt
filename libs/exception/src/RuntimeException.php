<?php

declare(strict_types=1);

namespace Phplrt\Exception;

use Phplrt\Contracts\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Lexer\TokenInterface;
use Phplrt\Contracts\Position\PositionInterface;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Position\Position;
use Phplrt\Source\File;

abstract class RuntimeException extends \RuntimeException implements RuntimeExceptionInterface
{
    private ?TokenInterface $token = null;

    private ?ReadableInterface $source = null;

    public function __construct(private string $original = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($this->original, $code, $previous);
    }

    public function getOriginalMessage(): string
    {
        return $this->original;
    }

    public function getSource(): ReadableInterface
    {
        if ($this->source === null) {
            return File::fromPathname($this->getFile());
        }

        return $this->source;
    }

    public function setSource(?ReadableInterface $source): void
    {
        $this->source = $source;

        $this->sync();
    }

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

    public function getPosition(): PositionInterface
    {
        return Position::fromOffset($this->getSource(), $this->getToken()->getOffset());
    }

    public function getToken(): TokenInterface
    {
        if ($this->token === null) {
            $position = Position::fromPosition($this->getSource(), \max(1, $this->getLine()));

            return new UndefinedToken($position);
        }

        return $this->token;
    }

    public function setToken(?TokenInterface $token): void
    {
        $this->token = $token;

        $this->sync();
    }

    private function getMessageSuffix(ReadableInterface $src, TokenInterface $token): string
    {
        $renderer = new ErrorInformationRenderer($src, $token);

        return \PHP_EOL . $renderer->render();
    }
}
