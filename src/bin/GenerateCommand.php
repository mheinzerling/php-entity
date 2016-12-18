<?php
declare(strict_types = 1);

namespace mheinzerling\entity\bin;


use mheinzerling\commons\FileUtils;
use mheinzerling\entity\config\Config;
use mheinzerling\entity\generator\ClassGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
    protected function configure()
    {
        $this->setName('generate')
            ->setAliases(['gen'])
            ->setDescription('Generate the classes described in the entities.json')
            ->addArgument('file', InputArgument::REQUIRED, 'Location of the entities.json')
            ->addOption("force", "f", InputOption::VALUE_NONE, "Overwrite all existing files, also in the source directory");
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $file = $input->getArgument('file');
        $force = $input->getOption('force');

        $output->writeln("Loading " . realpath($file));

        $root = realpath(dirname($file));
        $output->writeln("Generating files to " . $root);
        $config = Config::loadFile($file);
        $files = $config->generateFiles();
        foreach ($files as $path => $file) {


            $fullFile = FileUtils::append($root, $path);
            if (!file_exists($fullFile) || $file['overwrite'] == true || $force) {
                $output->writeln("[   WRITE] " . $path);
                FileUtils::createFile($fullFile, $file['content']);
            } else {
                $output->writeln("[    SKIP] " . $path);
            }
        }
        return 0;
    }


}