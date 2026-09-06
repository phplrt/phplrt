<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Command;

use Phplrt\Compiler\Compiler;
use Phplrt\Compiler\Generator\TargetPhpVersion;
use Phplrt\Source\FileSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'compile', description: 'Compile the passed grammar')]
final class GrammarCompileCommand extends Command
{
    protected function configure(): void
    {
        $this->addUsage('./resources/grammar.pp3 ./resources/grammar.php');

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

        // Note: An option carrying the values it is completed by is built as
        //       the definition itself, which is the only spelling symfony/console
        //       6.4 shares with the versions after it
        $inputDefinition = $this->getDefinition();

        $inputDefinition->addOption(new InputOption(
            name: 'class',
            shortcut: 'c',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The class name of the generated parser',
            suggestedValues: ['Parser'],
        ));

        $inputDefinition->addOption(new InputOption(
            name: 'namespace',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The namespace name of the generated parser',
            suggestedValues: ['App\\Parser'],
        ));

        $inputDefinition->addOption(new InputOption(
            name: 'use',
            shortcut: 'u',
            mode: InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            description: 'The list of class imports',
            default: [],
        ));

        $inputDefinition->addOption(new InputOption(
            name: 'php',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The target PHP version',
            suggestedValues: ['8.1', '8.2', '8.3', '8.4', '8.5', '8.6'],
        ));
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

    private function getTargetPhpVersion(InputInterface $input): ?TargetPhpVersion
    {
        $version = $input->getOption('php');

        if (!\is_string($version) || $version === '') {
            return null;
        }

        return TargetPhpVersion::fromString($version);
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

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $grammar = $this->getGrammarPathname($input);
        $pathname = $this->getOutputPathname($input);

        $logger = new ConsoleLogger($output);

        $output->writeln(\sprintf('Loading <comment>%s</comment> grammar', $grammar));

        $compiler = new Compiler();
        $compiler->setLogger($logger);
        $compiler->load(new FileSource($grammar));

        $assembly = $compiler->generate();

        foreach ($this->getClassImports($input) as $import) {
            $logger->debug('The generated parser imports {class}', [
                'class' => $import,
            ]);

            $assembly = $assembly->withClassImport($import);
        }

        $namespace = $this->getNamespaceName($input);
        if ($namespace !== null) {
            $logger->debug('The generated parser belongs to the {namespace} namespace', [
                'namespace' => $namespace,
            ]);
        }

        $class = $this->getClassName($input);
        if ($class !== null) {
            $logger->debug('The generated parser is named {class}', [
                'class' => $class,
            ]);
        }

        $php = $this->getTargetPhpVersion($input);
        if ($php !== null) {
            $logger->debug('The generated parser target is {php}', [
                'php' => $php->name,
            ]);

            $assembly = $assembly->withTargetPhpVersion($php);
        }

        $assembly
            ->withNamespaceName($namespace)
            ->withClassName($class)
        ->save($pathname);

        $logger->info('{bytes} byte(s) have been written into {pathname}', [
            'bytes' => \filesize($pathname),
            'pathname' => $pathname,
        ]);

        $output->writeln(\sprintf(' [<info>OK</info>] Generated into <comment>%s</comment>', $pathname));

        return self::SUCCESS;
    }
}
