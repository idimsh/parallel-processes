<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses\SampleCallbacks;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class SampleOnStartLog
{
    public function __invoke(
        ParallelCliProcesses $parallelCliProcesses,
        Process $process,
        SimpleCommand $command,
        string $commandId
    ) {
        if ($parallelCliProcesses->getLogger()) {
            $parallelCliProcesses->getLogger()->info(
                "in pre start callback for commandId: [{$commandId}]"
            );
        }
    }
}
