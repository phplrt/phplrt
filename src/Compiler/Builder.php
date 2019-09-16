<?php
/**
 * This file is part of phplrt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Phplrt\Compiler;

use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Phplrt\Contracts\Lexer\LexerInterface;
use Phplrt\Contracts\Parser\ParserInterface;

/**
 * Class Builder
 */
class Builder
{
    /**
     * @var string
     */
    private const CODE_HEADER = '
        This is an automatically generated file, which should not be manually edited.
    ';

    /**
     * @var Analyzer
     */
    private $analyzer;

    /**
     * Builder constructor.
     *
     * @param Analyzer $analyzer
     */
    public function __construct(Analyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function generate(string $name)
    {
        $file = new PhpFile();
        $file->addComment(self::CODE_HEADER);

        $class = $file->addClass($name);
        $class->setImplements([ParserInterface::class, LexerInterface::class]);

        return (new PsrPrinter())->printFile($file);
    }
}
