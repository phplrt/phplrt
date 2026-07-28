<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Generator;

use Phplrt\Compiler\CompilerResult;
use Phplrt\Compiler\Exception\CodeGenerationException;
use Phplrt\Compiler\Exception\GeneratorException;
use Phplrt\Compiler\Exception\InvalidClassNameException;
use Phplrt\Lexer\Builder\Definition\Lexer\EmbeddedLexerInterface;
use Phplrt\Lexer\Builder\Exception\LexerCompilerException;
use Phplrt\Lexer\Builder\LexerBuilderResult;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Writes the result of a compilation down as a PHP file returning the parser.
 *
 * The templates are written of the very result of the compilation, so nothing
 * of it is copied on the way: whatever the templates cannot spell on their own
 * is spelled by the printer.
 */
final class PhpOutputGenerator implements OutputGeneratorInterface
{
    /**
     * @var non-empty-string
     */
    private const string TEMPLATE_DIRECTORY = __DIR__ . '/../../resources/php';

    /**
     * @var non-empty-string
     */
    private const string TEMPLATE_ENTRYPOINT = 'parser.php.twig';

    /**
     * The names a class may be declared under.
     *
     * @var non-empty-string
     */
    private const string CLASS_NAME_PATTERN = '/^[a-z_\x80-\xff][a-z0-9_\x80-\xff]*+$/iu';

    private readonly Environment $twig;

    public function __construct(
        /**
         * Writes the pieces the parser is built of down.
         */
        private readonly PhpCodePrinter $printer = new PhpCodePrinter(),
    ) {
        $this->twig = new Environment(
            loader: new FilesystemLoader(paths: self::TEMPLATE_DIRECTORY),
            // The result is PHP code rather than a web page, so nothing of it
            // is ever escaped
            options: ['autoescape' => false],
        );

        $this->twig->addFunction(new TwigFunction('value', $this->printer->printValue(...)));
        $this->twig->addFunction(new TwigFunction('variable', $this->printer->printVariable(...)));
        $this->twig->addFunction(new TwigFunction('rule', $this->printer->printRule(...)));
        $this->twig->addFunction(new TwigFunction('reducer', $this->printer->printReducer(...)));
        $this->twig->addFunction(new TwigFunction('expression', $this->printer->printEmbeddedLexer(...)));
        $this->twig->addFilter(new TwigFilter('indent', $this->printer->indent(...)));
        $this->twig->addTest(new TwigTest('embedded', self::isEmbedded(...)));
    }

    public function generate(CompilerResult $result, OutputContext $context = new OutputContext()): string
    {
        self::assertFragmentsAreDefined($result->lexer);
        self::assertClassNameIsValid($context->class);

        try {
            $generated = $this->twig->render(self::TEMPLATE_ENTRYPOINT, [
                'namespace' => $context->namespace,
                'imports' => $context->imports,
                'class' => $context->class,
                'lexer' => $result->lexer,
                'parser' => $result->parser,
            ]);
        } catch (\Throwable $e) {
            throw self::findGeneratorException($e)
                ?? CodeGenerationException::becauseCodeIsNotGenerated($e);
        }

        return self::format($generated);
    }

    private static function isEmbedded(mixed $lexer): bool
    {
        return $lexer instanceof EmbeddedLexerInterface;
    }

    /**
     * Checks that the parser is named the way a class may be named.
     *
     * The name is written into the declaration as it is given, so a name that
     * is not a class name would only be noticed once the file is read by PHP.
     *
     * @throws InvalidClassNameException in case of the parser cannot be
     *         declared under the given name
     */
    private static function assertClassNameIsValid(?string $class): void
    {
        if ($class === null || \preg_match(self::CLASS_NAME_PATTERN, $class) === 1) {
            return;
        }

        throw InvalidClassNameException::becauseClassNameIsInvalid($class);
    }

    /**
     * Checks that every lexer the reading is handed over to is defined.
     *
     * A generated file refers to a lexer by the variable it has been put into,
     * so a name standing for nothing would only be noticed once the file is
     * read by PHP.
     *
     * @throws LexerCompilerException in case of a fragment is read by a lexer
     *         that is not defined
     */
    private static function assertFragmentsAreDefined(LexerBuilderResult $lexer): void
    {
        foreach ($lexer->transitions as $name) {
            if ($name !== null && !isset($lexer->lexers[$name])) {
                throw LexerCompilerException::becauseLexerIsNotDefined($name);
            }
        }

        foreach ($lexer->lexers as $nested) {
            if ($nested instanceof LexerBuilderResult) {
                self::assertFragmentsAreDefined($nested);
            }
        }
    }

    /**
     * Returns the error the generation has failed with, in case of it has
     * failed for a reason of its own.
     *
     * An error raised while a template is being rendered is reported by Twig as
     * an error of that template, so the reason it has been raised for is only
     * reachable through the errors it is wrapped into.
     */
    private static function findGeneratorException(\Throwable $error): ?GeneratorException
    {
        $current = $error;

        do {
            if ($current instanceof GeneratorException) {
                return $current;
            }
        } while (($current = $current->getPrevious()) !== null);

        return null;
    }

    /**
     * Writes the generated code down the way PHP code is written.
     *
     * A template says what the code is made of rather than how it is spaced, so
     * whatever spacing it has left behind is evened out here.
     */
    private static function format(string $code): string
    {
        $result = \preg_replace(
            ['/[ \t]++$/m', '/\n{3,}/'],
            ['', "\n\n"],
            $code,
        );

        \assert($result !== null, 'The generated code is written of the characters PHP is written of');

        return \rtrim($result) . "\n";
    }
}
