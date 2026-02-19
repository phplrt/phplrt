<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Lexer\Regex\MarkersRegexGenerator;
use Phplrt\Compiler\Lexer\Regex\RegexGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

/**
 * @template-extends OutputGenerator<Phplrt4GeneratedResult>
 */
final class Phplrt4OutputGenerator extends OutputGenerator
{
    private const string DEFAULT_TEMPLATE_DIRECTORY = __DIR__ . '/../../resources';

    private readonly Environment $twig;

    public function __construct(
        private readonly RegexGeneratorInterface $regex = new MarkersRegexGenerator(),
        /**
         * The namespace in which the specified file will be generated.
         *
         * If the namespace is empty, it will be absent from the generated code.
         *
         * ```
         * // namespace: ''
         * *nothing*
         *
         * // namespace: 'Example'
         * namespace Example;
         * ```
         *
         * Note: The option only makes sense (works) if the
         *       {@see Phplrt4OutputGenerator::$generateReturn} value
         *       is enabled.
         *
         * @var non-empty-string|null
         */
        private readonly ?string $namespace = null,
        /**
         * @var non-empty-string|null
         */
        private readonly ?string $class = null,
        /**
         * @var non-empty-string
         */
        private readonly string $template = 'lexer.php.twig',
    ) {
        $this->twig = new Environment(
            loader: new FilesystemLoader(
                paths: self::DEFAULT_TEMPLATE_DIRECTORY,
            ),
        );

        $this->twig->addFunction(new TwigFunction('value', $this->toGeneratedCode(...)));
        $this->twig->addFunction(new TwigFunction('regex', $this->regex->generate(...)));
    }

    public function generate(LexerBuilderResult $result): GeneratedResult
    {
        $generated = $this->twig->render($this->template, [
            'namespace' => $this->namespace,
            'class' => $this->class ?? '__GeneratedLexer' . \bin2hex(\random_bytes(4)),
            'token_class' => $this->namespace === null
                ? '__Token' . \bin2hex(\random_bytes(4))
                : 'Token',
            'token_eoi_class' => $this->namespace === null
                ? '__EndOfInput' . \bin2hex(\random_bytes(4))
                : 'EndOfInput',
            'is_anonymous_class' => $this->class === null,
            'input' => $result,
        ]);

        return new Phplrt4GeneratedResult($generated);
    }
}
