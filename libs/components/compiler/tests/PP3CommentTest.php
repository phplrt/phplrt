<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Tests;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Syntax\PP3\PP3Loader;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\ParserBuilder;
use Phplrt\Source\StringSource;
use Phplrt\Source\VirtualSource;
use Testo\Assert;
use Testo\Filter\Group;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Group('phplrt/compiler')]
#[Test]
final class PP3CommentTest extends TestCase
{
    private const PATHNAME = '/app/grammar.pp3';

    private LexerBuilder $lexer;

    private ParserBuilder $parser;

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->lexer = new LexerBuilder();
        $this->parser = new ParserBuilder();
    }

    public function testCommentIsAttachedToAToken(): void
    {
        $this->load(<<<'PP3'
            /**
             * The name of a thing.
             */
            %token T_NAME \w++
            PP3);

        [$name] = \array_values($this->lexer->tokens);

        Assert::same($name->comment, 'The name of a thing.');
    }

    public function testCommentIsAttachedToARule(): void
    {
        $this->load(<<<'PP3'
            %token T_NAME \w++

            /**
             * A thing.
             */
            Name : <T_NAME> ;
            PP3);

        Assert::same($this->parser->initial?->comment, 'A thing.');
    }

    public function testCommentIsWrittenAboutTheFileWhenAnEmptyLineFollowsIt(): void
    {
        $this->load(<<<'PP3'
            /**
             * Everything a thing is written of.
             */

            %token T_NAME \w++
            PP3);

        [$name] = \array_values($this->lexer->tokens);

        Assert::null($name->comment);
    }

    public function testLastOfSeveralCommentsIsAttached(): void
    {
        $this->load(<<<'PP3'
            /**
             * Everything a thing is written of.
             */

            /**
             * The name of a thing.
             */
            %token T_NAME \w++
            PP3);

        [$name] = \array_values($this->lexer->tokens);

        Assert::same($name->comment, 'The name of a thing.');
    }

    public function testCommentKeepsTheShapeItIsWrittenIn(): void
    {
        $this->load(<<<'PP3'
            /**
             * A name, like:
             *  - example
             *
             *  The rest of it.
             */
            %token T_NAME \w++
            PP3);

        [$name] = \array_values($this->lexer->tokens);

        Assert::same($name->comment, "A name, like:\n - example\n\n The rest of it.");
    }

    public function testCommentOfASingleLineIsNotRead(): void
    {
        $this->load(<<<'PP3'
            // The name of a thing.
            %token T_NAME \w++
            PP3);

        [$name] = \array_values($this->lexer->tokens);

        Assert::null($name->comment);
    }

    public function testCommentIsGeneratedAboveTheTokenConstant(): void
    {
        $code = $this->generate(<<<'PP3'
            /**
             * The name of a thing.
             */
            %token T_NAME \w++

            Name : <T_NAME> ;
            PP3);

        Assert::string($code)->contains(<<<'PHP'
                /**
                 * The name of a thing.
                 *
                 * @var int
                 */
                public const int T_NAME = 0;
            PHP);
    }

    public function testTokenWithoutACommentKeepsTheBlockOfASingleLine(): void
    {
        $code = $this->generate('%token T_NAME \w++' . "\n\nName : <T_NAME> ;");

        Assert::string($code)->contains("    /** @var int */\n    public const int T_NAME = 0;");
    }

    public function testCommentIsGeneratedAboveTheReducerMethod(): void
    {
        $code = $this->generate(<<<'PP3'
            %token T_NAME \w++

            /**
             * A thing.
             */
            Name -> { return $children; }
              : <T_NAME>
              ;
            PP3);

        Assert::string($code)->contains(<<<'PHP'
                /**
                 * A thing.
                 */
                private static function reduceName(
            PHP);
    }

    public function testReducerWithoutACommentIsGeneratedWithoutABlock(): void
    {
        $code = $this->generate(<<<'PP3'
            %token T_NAME \w++

            Name -> { return $children; }
              : <T_NAME>
              ;
            PP3);

        Assert::string($code)->contains(
            "    }\n\n    private static function reduceName(\\Phplrt\\Parser\\Context \$ctx, mixed \$children): mixed",
        );
    }

    private function load(string $source): array
    {
        $result = (new PP3Loader())
            ->load(VirtualSource::createFromString(self::PATHNAME, $source), $this->parser, $this->lexer);

        return \iterator_to_array($result, false);
    }

    private function generate(string $grammar): string
    {
        return (string) (new Compiler())
            ->load(StringSource::createFromString($grammar))
            ->generate();
    }
}
