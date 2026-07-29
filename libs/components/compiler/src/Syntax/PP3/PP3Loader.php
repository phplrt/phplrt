<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Syntax\PP3;

use Phplrt\Compiler\Compiler\SharedTokenLexerCompilerPass;
use Phplrt\Compiler\Exception\CompilerRuntimeException;
use Phplrt\Compiler\Exception\EmptyLexerException;
use Phplrt\Compiler\Exception\UnsupportedPragmaException;
use Phplrt\Compiler\Exception\UnsupportedPragmaValueException;
use Phplrt\Compiler\Exception\UnsupportedTokenActionException;
use Phplrt\Compiler\Node\Declaration\Declaration;
use Phplrt\Compiler\Node\Declaration\LexerDeclaration;
use Phplrt\Compiler\Node\Declaration\PragmaDeclaration;
use Phplrt\Compiler\Node\Declaration\TokenAction;
use Phplrt\Compiler\Node\Declaration\TokenDeclaration;
use Phplrt\Compiler\Syntax\PP2\PP2Loader;
use Phplrt\Contracts\Parser\ParserInterface;
use Phplrt\Contracts\Source\ReadableInterface;
use Phplrt\Lexer\Builder\Compiler\LexerCompilerPassInterface;
use Phplrt\Lexer\Builder\Definition\Lexer\PhpCodeEmbeddedLexer;
use Phplrt\Lexer\Builder\Definition\RegexModifier;
use Phplrt\Lexer\Builder\Definition\RegexTokenDefinition;
use Phplrt\Lexer\Builder\Definition\TokenDefinition;
use Phplrt\Lexer\Builder\LexerBuilder;
use Phplrt\Parser\Builder\Compiler\ParserCompilerPassInterface;
use Phplrt\Parser\Builder\ParserBuilder;

/**
 * Reads a PP3 grammar file into the lexer and the parser it describes.
 *
 * The format says what a token does by naming the action instead of naming the
 * state it ends up in, and configures the compilation by the settings of the
 * grammar rather than by the code calling it.
 */
final class PP3Loader extends PP2Loader
{
    /**
     * The name of the state a token belonging to every state is declared with.
     *
     * @var non-empty-string
     */
    private const string STATE_SHARED = '*';

    /**
     * Hands the reading over to the lexer of the named state.
     *
     * @var non-empty-string
     */
    private const string ACTION_STATE = 'state';

    /**
     * Gives the control back to the lexer that has entered this one.
     *
     * @var non-empty-string
     */
    private const string ACTION_EXIT = 'exit';

    /**
     * Emits the token to the named channel.
     *
     * @var non-empty-string
     */
    private const string ACTION_CHANNEL = 'channel';

    /**
     * The settings compiling the pattern of the lexer with a PCRE modifier and
     * without one.
     *
     * @var non-empty-string
     */
    private const string PRAGMA_LEXER_FLAG = 'lexer.pcre.flag';

    private const string PRAGMA_LEXER_NO_FLAG = 'lexer.pcre.disable';

    /**
     * The settings registering a pass of the lexer, named after the moment it
     * is processed at.
     *
     * @var array<non-empty-string, int>
     */
    private const array PRAGMA_LEXER_PASSES = [
        'lexer.pass' => LexerBuilder::PASS_PRIORITY_NORMALIZE,
        'lexer.check' => LexerBuilder::PASS_PRIORITY_CHECK,
        'lexer.optimize' => LexerBuilder::PASS_PRIORITY_OPTIMIZE,
        'lexer.complete' => LexerBuilder::PASS_PRIORITY_CHECK_AFTER_OPTIMIZE,
    ];

    /**
     * The settings registering a pass of the parser.
     *
     * @var array<non-empty-string, int>
     */
    private const array PRAGMA_PARSER_PASSES = [
        'parser.pass' => ParserBuilder::PASS_PRIORITY_NORMALIZE,
        'parser.check' => ParserBuilder::PASS_PRIORITY_CHECK,
        'parser.optimize' => ParserBuilder::PASS_PRIORITY_OPTIMIZE,
        'parser.complete' => ParserBuilder::PASS_PRIORITY_CHECK_AFTER_OPTIMIZE,
    ];

    /**
     * The settings dropping a pass that has been registered before.
     *
     * @var non-empty-string
     */
    private const string PRAGMA_LEXER_DISABLE = 'lexer.disable';

    private const string PRAGMA_PARSER_DISABLE = 'parser.disable';

    protected function createParser(): ParserInterface
    {
        return new PP3Parser();
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadDeclaration(
        Declaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void {
        if ($declaration instanceof LexerDeclaration) {
            $this->loadLexer($declaration, $source, $lexer);

            return;
        }

        parent::loadDeclaration($declaration, $source, $parser, $lexer);
    }

    /**
     * Reads the lexer a grammar has written by hand.
     *
     * @throws EmptyLexerException
     */
    private function loadLexer(
        LexerDeclaration $declaration,
        ReadableInterface $source,
        LexerBuilder $lexer,
    ): void {
        $code = $declaration->lexer->code;

        // An empty body ("-> {}") names a state that nothing reads
        if ($code === '') {
            throw EmptyLexerException::becauseLexerIsEmpty($source, $declaration);
        }

        $embedded = new PhpCodeEmbeddedLexer($code);
        $embedded->setSource($source, $declaration->offset, $declaration->length);

        $lexer->addEmbeddedLexer($declaration->name, $embedded);
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadToken(
        TokenDeclaration $declaration,
        ReadableInterface $source,
        LexerBuilder $lexer,
    ): void {
        if ($declaration->state !== self::STATE_SHARED) {
            parent::loadToken($declaration, $source, $lexer);

            return;
        }

        /**
         * A token belonging to every state is not added to any of them yet:
         * which states there are is only known once every grammar has been
         * read, so the declaration waits until the lexer is built.
         */
        $definition = new RegexTokenDefinition($declaration->pattern, $declaration->name);
        $definition->setHidden($declaration->isHidden);
        $definition->setSource($source, $declaration->offset, $declaration->length);

        SharedTokenLexerCompilerPass::of($lexer)
            ->addToken($definition);

        $this->loadAction($definition, $declaration, static::STATE_DEFAULT, $source);
    }

    /**
     * @throws UnsupportedTokenActionException
     */
    protected function loadAction(
        TokenDefinition $definition,
        TokenDeclaration $declaration,
        string $state,
        ReadableInterface $source,
    ): void {
        /**
         * A token is read once, so it goes to a single place afterwards: two
         * actions moving the reading would leave only the last of them, which
         * is not what a grammar saying both of them means.
         */
        $transition = null;

        foreach ($declaration->actions as $action) {
            switch ($action->name) {
                case self::ACTION_STATE:
                    $transition = self::readSingleTransition($action, $transition, $source);

                    $definition->enter(self::readArgument($action, $source));

                    break;

                case self::ACTION_EXIT:
                    $transition = self::readSingleTransition($action, $transition, $source);

                    self::assertActionHasNoArgument($action, $source);

                    $definition->exit();

                    break;

                case self::ACTION_CHANNEL:
                    $definition->setChannel(self::readArgument($action, $source));

                    break;

                default:
                    throw UnsupportedTokenActionException::becauseActionIsNotSupported($source, $action);
            }
        }
    }

    /**
     * @throws CompilerRuntimeException
     */
    protected function loadPragma(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        ParserBuilder $parser,
        LexerBuilder $lexer,
    ): void {
        $name = $declaration->name;

        if ($name === static::PRAGMA_ROOT) {
            $this->loadRootPragma($declaration, $source, $parser);

            return;
        }

        if ($name === self::PRAGMA_LEXER_FLAG) {
            $lexer->enable(self::readFlag($declaration, $source));

            return;
        }

        if ($name === self::PRAGMA_LEXER_NO_FLAG) {
            $lexer->disable(self::readFlag($declaration, $source));

            return;
        }

        if (isset(self::PRAGMA_LEXER_PASSES[$name])) {
            $lexer->addCompilerPass(
                pass: self::readPass($declaration, $source, LexerCompilerPassInterface::class),
                priority: self::PRAGMA_LEXER_PASSES[$name],
            );

            return;
        }

        if (isset(self::PRAGMA_PARSER_PASSES[$name])) {
            $parser->addCompilerPass(
                pass: self::readPass($declaration, $source, ParserCompilerPassInterface::class),
                priority: self::PRAGMA_PARSER_PASSES[$name],
            );

            return;
        }

        if ($name === self::PRAGMA_LEXER_DISABLE) {
            $lexer->removeCompilerPass(self::readClass($declaration, $source, LexerCompilerPassInterface::class));

            return;
        }

        if ($name === self::PRAGMA_PARSER_DISABLE) {
            $parser->removeCompilerPass(self::readClass($declaration, $source, ParserCompilerPassInterface::class));

            return;
        }

        throw UnsupportedPragmaException::becausePragmaIsNotSupported($source, $declaration);
    }

    /**
     * Returns the PCRE modifier the given setting names.
     *
     * A modifier is named either the way PCRE spells it or the way phplrt
     * calls it, so that a grammar may say "i" and "Caseless" alike.
     *
     * @throws UnsupportedPragmaValueException
     */
    private static function readFlag(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
    ): RegexModifier {
        $value = $declaration->value;

        foreach (RegexModifier::cases() as $modifier) {
            if ($modifier->name === $value || $modifier->value === $value) {
                return $modifier;
            }
        }

        throw UnsupportedPragmaValueException::becauseFlagIsNotSupported($source, $declaration);
    }

    /**
     * Returns the pass the given setting names.
     *
     * @template TArgPass of object
     * @param class-string<TArgPass> $expected
     * @return TArgPass
     * @throws UnsupportedPragmaValueException
     */
    private static function readPass(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        string $expected,
    ): object {
        $class = self::readClass($declaration, $source, $expected);

        try {
            return new $class();
        } catch (\Throwable $e) {
            throw UnsupportedPragmaValueException::becauseClassIsNotConstructable($source, $declaration, $e);
        }
    }

    /**
     * Returns the name of the class the given setting names.
     *
     * @template TArgPass of object
     * @param class-string<TArgPass> $expected
     * @return class-string<TArgPass>
     * @throws UnsupportedPragmaValueException
     */
    private static function readClass(
        PragmaDeclaration $declaration,
        ReadableInterface $source,
        string $expected,
    ): string {
        $class = \ltrim($declaration->value, '\\');

        if (!\class_exists($class)) {
            throw UnsupportedPragmaValueException::becauseClassIsNotFound($source, $declaration);
        }

        if (!\is_subclass_of($class, $expected)) {
            throw UnsupportedPragmaValueException::becauseClassIsNotAPass($source, $declaration, $expected);
        }

        return $class;
    }

    /**
     * Returns the action moving the reading, provided nothing has moved it
     * already.
     *
     * @throws UnsupportedTokenActionException in case of the reading has
     *         already been moved by another action
     */
    private static function readSingleTransition(
        TokenAction $action,
        ?TokenAction $previous,
        ReadableInterface $source,
    ): TokenAction {
        if ($previous !== null) {
            throw UnsupportedTokenActionException::becauseReadingIsMovedTwice($source, $action, $previous);
        }

        return $action;
    }

    /**
     * Returns the value the given action is written with.
     *
     * @return non-empty-string
     * @throws UnsupportedTokenActionException in case of the action is written
     *         with no value at all
     */
    private static function readArgument(TokenAction $action, ReadableInterface $source): string
    {
        return $action->argument
            ?? throw UnsupportedTokenActionException::becauseActionExpectsValue($source, $action);
    }

    /**
     * @throws UnsupportedTokenActionException in case of the action is written
     *         with a value it does nothing with
     */
    private static function assertActionHasNoArgument(TokenAction $action, ReadableInterface $source): void
    {
        if ($action->argument !== null) {
            throw UnsupportedTokenActionException::becauseActionExpectsNoValue($source, $action);
        }
    }
}
