<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Lexer;

use Phplrt\Compiler\Lexer\Compiler\AddMissingChannelsLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\ChannelNameDuplicationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\LexerCompilerPassInterface;
use Phplrt\Compiler\Lexer\Compiler\RegexDuplicationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\RegexExcessiveGreedLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\RegexValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\RemoveUnusedChannelsLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TokenNameDuplicationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TokenNameValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Compiler\TokenTransitionValidationLexerCompilerPass;
use Phplrt\Compiler\Lexer\Definition\RegexModifier;
use Phplrt\Compiler\Lexer\Exception\LexerCompilerException;
use Phplrt\Compiler\Lexer\Generator\GeneratedResult;
use Phplrt\Compiler\Lexer\Generator\OutputGeneratorInterface;
use Phplrt\Compiler\Lexer\Generator\Phplrt4OutputGenerator;
use Phplrt\Contracts\Lexer\Channel;
use Phplrt\Contracts\Lexer\ChannelInterface;

final class LexerBuilder extends LexerBuilderContext
{
    /**
     * @var array<array-key, ChannelInterface>
     */
    public private(set) array $channels = [];

    /**
     * @var array<non-empty-string, RegexModifier>
     */
    public private(set) array $flags = [
        RegexModifier::Compiled->value => RegexModifier::Compiled,
        RegexModifier::DotAll->value => RegexModifier::DotAll,
        RegexModifier::Utf8->value => RegexModifier::Utf8,
        RegexModifier::Multiline->value => RegexModifier::Multiline,
    ];

    /**
     * @var array<array-key, list<LexerCompilerPassInterface>>
     */
    public private(set) array $passes = [];

    /**
     * If a specific channel is defined, an "unknown" token (a token that
     * contains unknown data) will be added with the specified channel.
     */
    public private(set) ChannelInterface $unknown = Channel::Unknown;

    public function __construct()
    {
        $this->passes = [
            0 => [
                new TokenNameDuplicationLexerCompilerPass(),
                new TokenNameValidationLexerCompilerPass(),
                new RegexDuplicationLexerCompilerPass(),
                new RegexValidationLexerCompilerPass(),
                new RegexExcessiveGreedLexerCompilerPass(),
                new AddMissingChannelsLexerCompilerPass(),
                new ChannelNameDuplicationLexerCompilerPass(),
                new RemoveUnusedChannelsLexerCompilerPass(),
                new TokenTransitionValidationLexerCompilerPass(),
            ],
        ];
    }

    /**
     * @return $this
     */
    public function addCompilerPass(LexerCompilerPassInterface $pass, int $priority = 0): self
    {
        $this->passes[$priority][] = $pass;

        return $this;
    }

    public function enable(RegexModifier $flag): RegexModifier
    {
        return $this->flags[$flag->value] = $flag;
    }

    public function disable(RegexModifier $flag): RegexModifier
    {
        unset($this->flags[$flag->value]);

        return $flag;
    }

    /**
     * @api
     */
    public function setUnknownChannel(ChannelInterface $channel): self
    {
        $this->unknown = $channel;

        return $this;
    }

    /**
     * @return $this
     */
    public function removePcreFlagDefinition(RegexModifier $definition): self
    {
        foreach ($this->flags as $index => $flag) {
            if ($flag === $definition) {
                unset($this->flags[$index]);
            }
        }

        return $this;
    }

    /**
     * @param non-empty-string $namespace
     * @param callable(LexerBuilder):void $group
     *
     * @return $this
     * @throws LexerCompilerException
     */
    public function group(string $namespace, callable $group): self
    {
        $group($context = new LexerBuilder());

        foreach ($context->tokens as $token) {
            $this->addTokenDefinition($token->setState($namespace));
        }

        return $this;
    }

    /**
     * @param non-empty-string $name
     */
    public function channel(string $name): ChannelInterface
    {
        foreach ($this->channels as $channel) {
            if ($channel->value === $name) {
                return $channel;
            }
        }

        return $this->channels[] = Channel::tryFrom($name)
            ?? new readonly class ($name) implements ChannelInterface {
                public function __construct(
                    /**
                     * @var non-empty-string
                     */
                    public string $value,
                ) {}
            };
    }

    /**
     * @return $this
     */
    public function removeChannelDefinition(ChannelInterface $channel): self
    {
        foreach ($this->channels as $index => $actual) {
            if ($actual === $channel) {
                unset($this->channels[$index]);

                break;
            }
        }

        return $this;
    }

    /**
     * @template TArgGeneratedResult of GeneratedResult
     *
     * @param OutputGeneratorInterface<TArgGeneratedResult> $generator
     *
     * @return TArgGeneratedResult
     * @throws LexerCompilerException
     */
    public function build(
        OutputGeneratorInterface $generator = new Phplrt4OutputGenerator(),
    ): GeneratedResult {
        $context = $this->process();

        return $generator->generate(new LexerBuilderResult(
            tokens: \array_values($context->tokens),
            flags: \array_values($context->flags),
            channels: \array_values($context->channels),
        ));
    }
    /**
     * @throws LexerCompilerException
     */
    private function process(): self
    {
        $context = clone $this;

        try {
            foreach ($this->passes as $passes) {
                foreach ($passes as $pass) {
                    $pass->process($context);
                }
            }
        } catch (LexerCompilerException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw LexerCompilerException::becauseInternalErrorOccurs($e);
        }

        return $context;
    }
}
