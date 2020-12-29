<?php

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';

$loop              = \React\EventLoop\Factory::create();
$newProcessFactory = new \idimsh\ParallelProcesses\NewProcessFactory();
$processesConfig   = \idimsh\ParallelProcesses\BackgroundProcessesConfig::create();

$parallel = new ParallelCliProcesses(
    $processesConfig,
    $newProcessFactory,
    $loop
);
$parallel->execWithLoop(
    [
        'failed ls' => SimpleCommand::fromString(
            'exec /bin/bash -c "ls -la /tmp/not-found"'
        )->setAsShellEscaped(true),

        'ls tmp' => SimpleCommand::fromString(
            'exec /bin/bash -c "ls -lad /tmp"'
        )->setAsShellEscaped(true),

        'long failed grep exec in bash' => SimpleCommand::fromString(
            'exec /bin/bash -c "sleep 3; grep --color -rHn \'random string not there\' /usr /var/"'
        )->setAsShellEscaped(true),
    ]
);
$processesConfig->setCallbackOnBeforeStart(
    function (
        ParallelCliProcesses $parallelCliProcesses,
        Process $process,
        SimpleCommand $command,
        string $commandId
    ) {
        fputs(STDOUT, "Starting CommandId [{$commandId}], command: [" . $process->getCommandLine() . "]\n");
    }
);
$processesConfig->setCallbackProcessExit(
    function (
        Process $process,
        ?int $exitCode,
        ParallelCliProcesses $parallelCliProcesses,
        SimpleCommand $command,
        string $commandId
    ) {
        if ($exitCode && $exitCode > 0) {
            fputs(STDERR, "Stopping all since CommandId [{$commandId}] failed with exit code [{$exitCode}]\n");
            $parallelCliProcesses->stopAll();
            // we can also call:
            // if ($commandId === 'failed ls') $parallelCliProcesses->stopCommand('long failed grep exec in bash');
        }
        elseif ($exitCode === 0) {
            fputs(STDERR, "CommandId [{$commandId}] success\n");
        }
        else {
            fputs(STDERR, "CommandId [{$commandId}] exit code unknown\n");
        }
    }
);
$processesConfig->setCallbackProcessStreamRead(
    function (
        $type,
        $data,
        ParallelCliProcesses $parallelCliProcesses,
        Process $process,
        SimpleCommand $command,
        string $commandId
    ) {
        if ($type === Process::OUT) {
            fputs(STDOUT, "CommandId [{$commandId}] OUT\n" . $data);
        }
        if ($type === Process::ERR) {
            fputs(STDERR, "CommandId [{$commandId}] ERR\n" . $data);
        }
    }
);

$loop->run();
