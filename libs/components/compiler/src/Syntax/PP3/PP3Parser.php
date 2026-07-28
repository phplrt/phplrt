<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP3;

use Phplrt\Compiler\Node\Declaration\Declaration;
use Phplrt\Contracts\Parser\Exception\ParserExceptionInterface;
use Phplrt\Contracts\Parser\Exception\RuntimeExceptionInterface;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;

final readonly class PP3Parser implements ParserInterface
{
    /**
     * @var ParserInterface<array<Declaration>>
     */
    private ParserInterface $runtime;

    public function __construct()
    {
        $lexer = PP3LexerBuilder::create()
            ->build();

        $this->runtime = PP3ParserBuilder::create()
            ->build($lexer)
            ->toParser($lexer->toLexer());
    }

    /**
     * @return array<Declaration>
     * @throws ParserExceptionInterface
     * @throws RuntimeExceptionInterface
     */
    public function parse(ReadableInterface $source): array
    {
        $declarations = $this->runtime->parse($source);

        \assert(\is_array($declarations), 'A grammar file is read into a list of declarations');

        $result = [];

        foreach ($declarations as $declaration) {
            \assert($declaration instanceof Declaration, 'A grammar file is written of declarations');

            $result[] = $declaration;
        }

        return $result;
    }
}
