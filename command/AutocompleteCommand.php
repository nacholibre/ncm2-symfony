<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class AutocompleteCommand extends Command
{
    protected static $defaultName = 'complete';

    protected function configure()
    {
        $this->setDescription('This command is executed by the neovim script to get the autocomplete results');
        $this->addOption(
            'directory',
            'd',
            InputOption::VALUE_REQUIRED,
            'Directory',
            false
        );
        $this->addOption(
            'position',
            'p',
            InputOption::VALUE_REQUIRED,
            'Position',
            false
        );
        $this->addOption(
            'extension',
            'e',
            InputOption::VALUE_REQUIRED,
            'Extension',
            false
        );
    }

    private function checkIsSymfonyProject($directory) {
        $filesystem = new Filesystem();

        if (!$filesystem->exists($directory.'/composer.json')) {
            return false;
        }

        $composerData = json_decode(file_get_contents($directory.'/composer.json'), true);

        if (isset($composerData['require']['symfony/framework-bundle'])) {
            return true;
        }

        return false;
    }

    private function getTwigData($directory) {
        $process = new Process(['php', $directory.'/bin/console', 'debug:twig', '--format=json']);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return json_decode($output, true);
    }

    private function getRoutes($directory) {
        $process = new Process(['php', $directory.'/bin/console', 'debug:router', '--format=json']);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        return json_decode($output, true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO check if projectc is symfony project
        // check if composer file is located
        $directory = $input->getOption('directory');
        $extension = $input->getOption('extension');

        $autocompleteResponse = [];

        if (!$this->checkIsSymfonyProject($directory)) {
            $output->writeln(json_encode([
                'suggestions' => [],
            ]));
            return 0;
        }

        if (!in_array($extension, ['php', 'twig'])) {
            $output->writeln(json_encode([
                'suggestions' => [],
            ]));
            return 0;
        }

        $lines = '';
        while( $line = fgets(STDIN) ) {
            $lines .= $line;
        }

        if ($extension == 'php') {
            $paths = $this->getRoutes($directory);
            foreach($paths as $routeName => $data) {
                $autocompleteResponse[] = [
                    'short_description' => 'Route ' . $data['defaults']['_controller'],
                    'name' => $routeName,
                ];
            }
        }

        if ($extension == 'twig') {
            $twigData = $this->getTwigData($directory);
            foreach ($twigData['tests'] as $d) {
                $autocompleteResponse[] = [
                    'short_description' => 'Twig test',
                    'name' => $d,
                ];
            }

            foreach ($twigData['globals'] as $k => $d) {
                $autocompleteResponse[] = [
                    'short_description' => 'Twig global variable',
                    'name' => $k,
                ];
            }

            foreach ($twigData['filters'] as $k => $arguments) {
                $description = '';
                if ($arguments && count($arguments) > 0) {
                    $description = $k.'('.implode(', ', $arguments) . ')';
                }

                $description = 'TwigFilter ' . $description;
                $autocompleteResponse[] = [
                    'short_description' => $description,
                    'name' => $k,
                ];
            }

            foreach ($twigData['functions'] as $k => $arguments) {
                $description = '';
                if ($arguments && count($arguments) > 0) {
                    $description = $k.'('.implode(', ', $arguments) . ')';
                }

                $description = 'TwigFunction ' . $description;
                $autocompleteResponse[] = [
                    'short_description' => $description,
                    'name' => $k,
                ];
            }
        }

        $jsonOutput = json_encode([
            'suggestions' => $autocompleteResponse,
        ]);

        $output->writeln($jsonOutput);

        return 0;
    }
}
