<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses\SampleCallbacks;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use Symfony\Component\Process\Process;

/**
 * @codeCoverageIgnore
 */
class SampleOnExitStopAllIfError
{
    public function __invoke(
        Process $process,
        ?int $exitCode,
        ParallelCliProcesses $parallelCliProcesses,
        SimpleCommand $command,
        string $commandId
    ) {
        $isFailed = $exitCode && $exitCode > 0;
        if ($parallelCliProcesses->getLogger()) {
            $parallelCliProcesses->getLogger()->info(
                "in exit callback for commandId: [{$commandId}]" .
                " exit code: " . var_export($exitCode, true) .
                " is failed: " . var_export($isFailed, true)
            );
        }
        if ($isFailed) {
            if ($parallelCliProcesses->getLogger()) {
                $parallelCliProcesses->getLogger()->info("stopping all");
            }
            $parallelCliProcesses->stopAll();
        }
    }
}
