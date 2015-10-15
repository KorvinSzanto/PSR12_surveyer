<?php
namespace Fig\Console\Command;

use Fig\Scan\ScanJob;
use Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command
{

    protected $filesystem;

    protected $pID;

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
            ->setName('scan')
            ->addArgument('src', InputArgument::REQUIRED, 'Source directory')
            ->addOption('workers', 'w', InputOption::VALUE_OPTIONAL, 'The number of workers to use', 6)
            ->setDescription('Scan statements');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($base_path = realpath($input->getArgument('src'))) {
            $workers = intval($input->getOption('workers'));
            $job = new ScanJob($output, $this->filesystem, $base_path, $workers);
            $job->scan();
        }

        exit;
    }
}
