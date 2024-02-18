<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Printer\Value;

use Phplrt\Compiler\Printer\PrinterInterface;
use Phplrt\Compiler\Printer\Style;
use Phplrt\Parser\Context;

final class PhpLanguageInjection extends LanguageInjection
{
    /**
     * @var array<non-empty-string, non-empty-string>
     */
    private const CODE_INJECTIONS = [
        '$file'   => '$file = $ctx->source;',
        '$source' => '$source = $ctx->source;',
        '$offset' => '$offset = $ctx->lastProcessedToken->getOffset();',
        '$token'  => '$token = $ctx->lastProcessedToken;',
        '$state'  => '$state = $ctx->state;',
        '$rule'   => '$rule = $ctx->rule;',
    ];

    /**
     * @var non-empty-string
     */
    private const TPL_CODE_INJECTION = '// The "%s" variable is an auto-generated';

    public function print(PrinterInterface $printer): string
    {
        return $this->getSignaturePrefix()
            . $this->getSignature()
            . $this->getTypeHint()
            . $this->getCodeBody($printer->getStyle());
    }

    private function getCodeBody(Style $style): string
    {
        $body = <<<'PHP'
             {
                %s
            }
            PHP;

        return \vsprintf($body, [
            $this->getCode($style),
        ]);
    }

    private function getSignaturePrefix(): string
    {
        if ($this->isStatic()) {
            return 'static ';
        }

        return '';
    }

    private function getSignature(): string
    {
        return \vsprintf('function (%s$ctx, %s$children)', [
            '\\' . Context::class . ' ',
            '', // Since PHP 8.0 'mixed ',
        ]);
    }

    private function getTypeHint(): string
    {
        if ($this->isVoid()) {
            return ': void';
        }

        return '';
        // Since PHP 8.0 return ': mixed';
    }

    private function getCodeGeneratedValues(Style $style): string
    {
        $injections = [];

        foreach (self::CODE_INJECTIONS as $variable => $addition) {
            if ($this->contains($variable)) {
                $injections[] = \sprintf(self::TPL_CODE_INJECTION, $variable);
                $injections[] = $style->indent($addition)
                    . $style->lineDelimiter;
            }
        }

        return \implode($style->lineDelimiter, $injections);
    }

    private function getCode(Style $style): string
    {
        $prefix = $this->getCodeGeneratedValues($style);

        if ($prefix === '') {
            return \trim($this->code);
        }

        return $prefix . $style->lineDelimiter
            . $style->indentation . \trim($this->code);
    }

    private function isVoid(): bool
    {
        return !$this->contains('return');
    }

    private function isStatic(): bool
    {
        return !$this->contains('$this->');
    }
}
