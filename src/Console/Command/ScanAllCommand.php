<?php
namespace Fig\Console\Command;

use Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScanAllCommand extends Command
{

    protected $filesystem;

    protected $cmd = "php70 ./bin/scanner scan";

    /**
     * ScanComposerCommand constructor.
     * @param $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('scan-all')
            ->addArgument('src', InputArgument::REQUIRED, 'Source directory')
            ->addArgument('output', InputArgument::REQUIRED, 'Output directory')
            ->setDescription('Scan all directories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($source = realpath($input->getArgument('src'))) && ($output_dir = realpath($input->getArgument('output')))) {
            $all_files = $this->filesystem->listDirectory($source);

            foreach ($all_files as $file) {
                $name = basename($file);
                if (is_dir("{$source}/{$file}")) {
                    $output->writeln("Scanning {$file}");
                    shell_exec("{$this->cmd} {$source}/{$file} > {$output_dir}/{$name}.txt");
                }
            }
        }
    }

}
