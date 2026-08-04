<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Command;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\CompilerResult;
use Phplrt\Contracts\Source\FileInterface;
use Phplrt\Source\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'check', description: 'Compile the passed grammar', aliases: ['validate'], usages: [
    './resources/grammar.pp',
    './resources/grammar.pp2',
    './resources/grammar.pp3',
])]
final class GrammarCheckCommand extends Command
{
    #[\Override]
    protected function configure(): void
    {
        $this->addArgument(
            name: 'grammar',
            mode: InputArgument::REQUIRED,
            description: 'The pathname to the grammar file to use',
        );
    }

    /**
     * @return non-empty-string
     */
    private function getGrammarPathname(InputInterface $input): string
    {
        $grammar = $input->getArgument('grammar');

        if (!\is_string($grammar) || $grammar === '') {
            throw new \InvalidArgumentException('The [grammar] must be a string to the grammar file');
        }

        $result = \realpath($grammar);

        if ($result === false) {
            return $grammar;
        }

        return $result;
    }

    private function getRulesBeforeOptimization(Compiler $compiler): int
    {
        return $compiler->parser->rules->count();
    }

    private function getRulesAfterOptimization(CompilerResult $result): int
    {
        return \count($result->parser->grammar);
    }

    private function getTokensBeforeOptimization(Compiler $compiler): int
    {
        return \count($compiler->lexer->tokens);
    }

    private function getTokensAfterOptimization(CompilerResult $result): int
    {
        return \count($result->lexer->tokens);
    }

    /**
     * @return list<non-empty-string>
     */
    private function getLoadedFiles(Compiler $compiler): array
    {
        $result = [];

        foreach ($compiler->parser->rules as $definition) {
            $source = $definition->context?->source;

            if (!$source instanceof FileInterface) {
                continue;
            }

            $pathname = \realpath($source->pathname);

            if ($pathname === false) {
                $pathname = $source->pathname;
            }

            $result[$pathname] = true;
        }

        return \array_keys($result);
    }

    private function getLookaheadTableSize(CompilerResult $result): int
    {
        $count = 0;

        foreach ($result->parser->lookahead as $tokens) {
            $count += \count($tokens ?? []);
        }

        return $count;
    }

    private function getEmptyRulesTableSize(CompilerResult $result): int
    {
        $count = 0;

        // A rule that may begin with any token at all is a rule that reads the
        // empty input, which is the only way it may begin with all of them
        foreach ($result->parser->lookahead as $tokens) {
            if ($tokens === null) {
                ++$count;
            }
        }

        return $count;
    }

    private function getKeptTableSize(CompilerResult $result): int
    {
        $count = 0;

        foreach ($result->parser->kept as $isKept) {
            if ($isKept) {
                ++$count;
            }
        }

        return $count;
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $grammar = $this->getGrammarPathname($input);

        $output->writeln(\sprintf('Checking <comment>%s</comment> grammar', $grammar));

        $compiler = new Compiler()
            ->load(new File($grammar));

        $loaded = $this->getLoadedFiles($compiler);

        $output->writeln(\sprintf('Loaded <comment>%d</comment> grammar files:', \count($loaded)));
        foreach ($loaded as $file) {
            $output->writeln(\sprintf('  - <comment>%s</comment>', $file));
        }

        $result = $compiler->build();

        $output->writeln('');
        $output->writeln('[OK] <info>grammar is valid</info>');

        if (!$output->isVerbose()) {
            return self::SUCCESS;
        }

        $output->writeln('');
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// List of rules that perform the construction of AST nodes</>');
        }
        $output->writeln(\sprintf(' Reducers:  <info>%d</info>', \count($result->parser->reducers)));
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// Lookahead table / non-optimizable rules size (more / less is better)</>');
        }
        $output->writeln(\vsprintf(' Lookahead: <info>%d</info> / <info>%d</info>', [
            $this->getLookaheadTableSize($result),
            $this->getEmptyRulesTableSize($result),
        ]));
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// List of rules that requires tracing (less is better)</>');
        }
        $output->writeln(\sprintf(' Kept:      <info>%d</info>', $this->getKeptTableSize($result)));
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// List of tokens that have specific (non-default) channels</>');
        }
        $output->writeln(\sprintf(' Channels:  <info>%d</info>', \count($result->lexer->channels)));
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// List of tokens that capture subgroups (less is better)</>');
        }
        $output->writeln(\sprintf(' Subgroups: <info>%d</info>', \count($result->lexer->subgroups)));
        if ($output->isVeryVerbose()) {
            $output->writeln('');
            $output->writeln(' <fg=gray>// Size of the final regular expression (less is better)</>');
        }
        $output->writeln(\sprintf(' PCRE:      <info>%d</info> bytes', \strlen($result->lexer->pattern)));

        $output->writeln('');
        $output->writeln(' <comment>Rules:</comment>');
        if ($output->isVeryVerbose()) {
            $output->writeln('   <fg=gray>// The number of rules loaded into the grammar</>');
        }
        $output->writeln(\sprintf('   Loaded: %d', $this->getRulesBeforeOptimization($compiler)));
        if ($output->isVeryVerbose()) {
            $output->writeln('   <fg=gray>// Number of rules after optimization of transition rules</>');
        }
        $output->writeln(\sprintf('   After:  <info>%d</info>', $this->getRulesAfterOptimization($result)));

        $output->writeln('');
        $output->writeln(' <comment>Tokens:</comment>');
        if ($output->isVeryVerbose()) {
            $output->writeln('   <fg=gray>// The number of known tokens loaded into the grammar</>');
        }
        $output->writeln(\sprintf('   Loaded: %d', $this->getTokensBeforeOptimization($compiler)));
        if ($output->isVeryVerbose()) {
            $output->writeln('   <fg=gray>// The number of compiled tokens</>');
        }
        $output->writeln(\sprintf('   After:  <info>%d</info>', $this->getTokensAfterOptimization($result)));

        return self::SUCCESS;
    }
}
