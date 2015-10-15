<?php
namespace Fig\Scan;

use Symfony\Component\Console\Output\OutputInterface;

class ScanJob
{

    /** @type \Symfony\Component\Console\Output\OutputInterface */
    protected $output;

    /** @type string */
    protected $basePath;

    /** @type int The number of workers */
    protected $workers;

    /** @type array A list of process IDs */
    protected $processes = [];

    /** @type \Filesystem */
    protected $filesystem;

    public function __construct(OutputInterface $output, \Filesystem $filesystem, $base_path, $workers = 6)
    {
        $this->output = $output;
        $this->basePath = $base_path;
        $this->workers = $workers;
        $this->filesystem = $filesystem;
    }

    public function scan()
    {
        $output = $this->output;
        if (!\PhutilXHPASTBinary::isAvailable()) {
            $output->writeln(\PhutilXHPASTBinary::getBuildInstructions(), $output::OUTPUT_NORMAL);
            exit;
        }

        $base_path = $this->basePath;
        $php_files = $this->allFiles($base_path);

        $total_files = count($php_files);
        $this->output->writeln("{$total_files} files to scan");

        /** @type \Generator $split */
        $split = $this->chunk($php_files, ceil($total_files / $this->workers));

        foreach ($split as $chunk) {
            $this->forkProcess($chunk);
        }

        foreach ($this->processes as $pID) {
            pcntl_waitpid($pID, $status);
        }
    }

    protected function chunk($array, $chunk_size)
    {
        $total = count($array);
        $i = 0;

        do {
            yield array_slice($array, $i, $chunk_size);
            $i += $chunk_size;
        } while ($i < $total);
    }

    protected function forkProcess(array $chunk)
    {
        if ($process_id = pcntl_fork()) {
            $this->processes[] = $process_id;
            return;
        }

        $process = new ScanProcess($this->output, $this->filesystem, $chunk, $this->basePath);
        $process->scan();
        exit;
    }

    protected function allFiles($directory)
    {
        $files = array();

        foreach ($this->filesystem->listDirectory($directory) as $file) {
            $full_path = "{$directory}/{$file}";

            if (is_dir($full_path)) {
                $files = array_merge($files, $this->allFiles($full_path));
                continue;
            }

            $file_extension = strtolower(substr(basename($file), -3));
            if ($file_extension == 'php') {
                $files[] = $full_path;
            }
        }

        return $files;
    }

}
