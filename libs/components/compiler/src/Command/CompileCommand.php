<?php

declare(strict_types=1);

namespace Phplrt\Compiler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

abstract class CompileCommand extends Command
{
    #[\Override]
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
    }

    /**
     * @return non-empty-string
     */
    protected function getGrammarPathname(InputInterface $input): string
    {
        $grammar = $input->getArgument('grammar');

        if (!\is_string($grammar) || $grammar === '') {
            throw new \InvalidArgumentException('The [grammar] must be a string to the grammar file');
        }

        return $grammar;
    }

    /**
     * @return non-empty-string
     */
    protected function getOutputPathname(InputInterface $input): string
    {
        $output = $input->getArgument('output');

        if (!\is_string($output) || $output === '') {
            throw new \InvalidArgumentException('The [output] must be a string to the output php file');
        }

        return $output;
    }
}
