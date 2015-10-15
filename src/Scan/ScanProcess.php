<?php
namespace Fig\Scan;

use AASTNode;
use Symfony\Component\Console\Output\OutputInterface;
use XHPASTTree;

class ScanProcess
{

    /** @type string[] The paths to the files that need scanned */
    protected $files;

    /** @type \Symfony\Component\Console\Output\OutputInterface */
    protected $output;

    /** @type string */
    protected $basePath;

    /** @type \Filesystem  */
    protected $filesystem;

    /** @type string The process ID */
    protected $pID;

    public function __construct(OutputInterface $output, \Filesystem $filesystem, array $files, $base_path)
    {
        $this->files = $files;
        $this->filesystem = $filesystem;
        $this->output = $output;
        $this->basePath = $base_path;
        $this->pID = getmypid();
    }

    public function scan()
    {
        foreach ($this->files as $file) {
            $this->scanFile($file);
        }

        $output = $this->output;
        if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
            $output->writeln("{$this->pID}: Done.");
        }
    }

    protected function scanFile($file)
    {
        $output = $this->output;
        $base_path = $this->basePath;
        $obj = [
            'file' => substr(realpath($file), strlen($base_path)),
            'parsed' => true,
            'process' => $this->pID,
            'statements' => []
        ];

        try {
            if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
                $output->writeln("{$this->pID}: Scanning {$file}");
            }

            set_time_limit(10);

            $tree = XHPASTTree::newFromData($this->filesystem->readFile($file));

            if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
                $output->writeln("{$this->pID}: Parsed {$file}");
            }

            /** @type AASTNode[] $nodes */
            $nodes = $tree->getRootNode()->selectDescendantsOfType('n_IF');
            foreach ($nodes as $node) {
                $if_statement = explode("\n", $node->getConcreteString());
                $opening_statement = array_shift($if_statement);

                while (strpos($opening_statement, '{') === false && strpos($opening_statement, ':') === false) {
                    if (!count($if_statement)) {
                        break;
                    }
                    $opening_statement .= PHP_EOL . array_shift($if_statement);
                }

                if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
                    $output->writeln("{$this->pID}: Statement Built {$file}");
                }

                $obj['statements'][$node->getLineNumber()][] = $opening_statement;
            }
        } catch (\Exception $e) {
            $obj['parsed'] = false;
        }

        if ($output->getVerbosity() >= $output::VERBOSITY_VERBOSE) {
            $output->writeln("{$this->pID}: Outputting {$file}");
        }

        if (count($obj['statements'])) {
            $output->writeln(json_encode($obj));
        }
    }

}
