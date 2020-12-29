<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses\SampleCallbacks;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use React\Stream\WritableStreamInterface;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class SampleOnReadDumpOutAndError
{
    /**
     * @var WritableStreamInterface
     */
    protected $writableStream;

    public function __construct(
        WritableStreamInterface $writableStream
    ) {
        $this->writableStream = $writableStream;
    }

    public function __invoke(
        $type,
        $data,
        ParallelCliProcesses $parallelCliProcesses,
        Process $process,
        SimpleCommand $command,
        string $commandId
    ): void {
        $out = $process->getIncrementalOutput();
        $err = $process->getIncrementalErrorOutput();
        if ($out) {
            $this->writableStream->write(
                $commandId . '####out' . "\n" . $out
            );
        }
        if ($err) {
            $this->writableStream->write(
                $commandId . '####err' . "\n" . $err
            );
        }
    }
}
