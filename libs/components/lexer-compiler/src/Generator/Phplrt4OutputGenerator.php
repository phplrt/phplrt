<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer\Generator;

use Phplrt\Compiler\Lexer\Definition\RegexTokenDefinition;
use Phplrt\Compiler\Lexer\Definition\TokenDefinition;
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;
use Phplrt\Compiler\Lexer\LexerBuilderResult;
use Phplrt\Compiler\Lexer\Regex\MarkersRegexGenerator;
use Phplrt\Compiler\Lexer\Regex\RegexGeneratorInterface;
use Phplrt\Compiler\Lexer\Regex\RegexGeneratorResult;
use Phplrt\Contracts\Lexer\Channel;

/**
 * @template-extends OutputGenerator<Phplrt4GeneratedResult>
 */
final readonly class Phplrt4OutputGenerator extends OutputGenerator
{
    public function __construct(
        private RegexGeneratorInterface $regex = new MarkersRegexGenerator(),
        /**
         * Enable or disable the generation of PHP opening tag.
         *
         * This option can be used to embed a template into another file.
         *
         * ```
         * // generateOpenTag: true
         * <?php
         *
         * // generateOpenTag: false
         * *nothing*
         * ```
         *
         * Note: The option only makes sense (works) if the
         *       {@see Phplrt4OutputGenerator::$generateReturn} value
         *       is enabled.
         */
        private bool $generateOpenTag = true,
        /**
         * Enable or disable the generation of PHP "strict types" declaration.
         *
         * This option can be used to embed a template into another file.
         *
         * ```
         * // generateStrictTypes: true
         * declare(strict_types=1);
         *
         * // generateStrictTypes: false
         * *nothing*
         * ```
         *
         * Note: The option only makes sense (works) if the
         *       {@see Phplrt4OutputGenerator::$generateReturn} value
         *       is enabled.
         */
        private bool $generateStrictTypes = true,
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
         */
        private string $namespace = '',
        /**
         * Contains additional injectable PHP code before the declaration of
         * the configuration DTO.
         *
         * ```
         * // code: ''
         * *nothing*
         *
         * // code: 'echo "Hello, World!";'
         * echo "Hello, World";
         * ```
         *
         * Note: The option only makes sense (works) if the
         *       {@see Phplrt4OutputGenerator::$generateReturn} value
         *       is enabled.
         */
        private string $code = '',
    ) {}

    public function generate(LexerBuilderResult $result): GeneratedResult
    {


        if ($this->code !== '') {
            $template = "{$this->code}\n\n{$template}";
        }

        if ($this->namespace !== '') {
            $template = \sprintf('namespace %s;', $this->namespace)
                . "\n\n{$template}";
        }

        if ($this->generateStrictTypes) {
            $template = "declare(strict_types=1);\n\n{$template}";
        }

        if ($this->generateOpenTag) {
            $template = "<?php\n\n{$template}";
        }

        return new Phplrt4GeneratedResult(
            result: $template,
            pattern: $result->pattern,
        );
    }

    /**
     * @param list<TokenDefinition> $tokens
     *
     * @return list<TokenDefinition>
     */
    private function withUnknownToken(array $tokens): array
    {
        $tokens[] = new RegexTokenDefinition('.+?')
            ->setChannel(Channel::Unknown);

        return $tokens;
    }

    /**
     * @param array<non-empty-string, non-empty-string|int> $aliases
     * @param array<non-empty-string, non-empty-string> $channels
     *
     * @return non-empty-string
     */
    private function generateStateCodeTemplate(
        RegexGeneratorResult $result,
        array $aliases,
        array $channels,
    ): string {
        return \sprintf(
            <<<'PHP'
                new \Phplrt\Lexer\LexerStateCreateInfo(
                    pattern: %s,
                    channels: %s,
                    aliases: %s,
                )
                PHP,
            $this->toGeneratedCode($result->pattern),
            $this->toGeneratedCode($channels, \count($channels) < 2)
                ->setArrayDepth(1),
            $this->toGeneratedCode($aliases, \count($aliases) < 2)
                ->setArrayDepth(1),
        );
    }
}
