<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Command;

use Phplrt\Compiler\Compiler;
use Phplrt\Source\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'compile', description: 'Compile the passed grammar', usages: [
    './resources/grammar.pp3 ./resources/grammar.php',
])]
final class GrammarCompileCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            name: 'grammar',
            mode: InputArgument::REQUIRED,
            description: 'The pathname to the grammar file to use',
        );

        $this->addArgument(
            name: 'output',
            mode: InputArgument::REQUIRED,
            description: 'The output file to use',
        );

        $this->addOption(
            name: 'class',
            shortcut: 'c',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The class name of the generated parser',
            suggestedValues: ['Parser'],
        );

        $this->addOption(
            name: 'namespace',
            shortcut: 'ns',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The namespace name of the generated parser',
            suggestedValues: ['App\\Parser'],
        );

        $this->addOption(
            name: 'use',
            shortcut: 'u',
            mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            description: 'The list of class imports',
            default: [],
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

    /**
     * @return non-empty-string
     */
    private function getOutputPathname(InputInterface $input): string
    {
        $output = $input->getArgument('output');

        if (!\is_string($output) || $output === '') {
            throw new \InvalidArgumentException('The [output] must be a string to the output php file');
        }

        $result = \realpath($output);

        if ($result === false) {
            return $output;
        }

        return $result;
    }

    /**
     * @return non-empty-string|null
     */
    private function getClassName(InputInterface $input): ?string
    {
        $name = $input->getOption('class');

        if (!\is_string($name) || $name === '') {
            return null;
        }

        return $name;
    }

    /**
     * @return non-empty-string|null
     */
    private function getNamespaceName(InputInterface $input): ?string
    {
        $name = $input->getOption('namespace');

        if (!\is_string($name) || $name === '') {
            return null;
        }

        return $name;
    }

    /**
     * @return list<non-empty-string>
     */
    private function getClassImports(InputInterface $input): array
    {
        $imports = $input->getOption('use');

        if (!\is_array($imports)) {
            return [];
        }

        $result = [];

        foreach ($imports as $import) {
            if (!\is_string($import) || $import === '') {
                continue;
            }

            $result[] = $import;
        }

        return $result;
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $grammar = $this->getGrammarPathname($input);
        $pathname = $this->getOutputPathname($input);

        $output->writeln(\sprintf('Loading <comment>%s</comment> grammar', $grammar));

        $assembly = new Compiler()
            ->load(new File($grammar))
        ->generate();

        foreach ($this->getClassImports($input) as $import) {
            $assembly->withClassImport($import);
        }

        $assembly
            ->withNamespaceName($this->getNamespaceName($input))
            ->withClassName($this->getClassName($input))
        ->save($pathname);

        $output->writeln(\sprintf(' [<info>OK</info>] Generated into <comment>%s</comment>', $pathname));

        return self::SUCCESS;
    }
}
