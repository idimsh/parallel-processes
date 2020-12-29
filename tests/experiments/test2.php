<?php

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\SampleCallbacks\SampleOnExitStopAllIfError;
use idimsh\ParallelProcesses\SampleCallbacks\SampleOnReadDumpOutAndError;
use idimsh\ParallelProcesses\SampleCallbacks\SampleOnStartLog;

require_once __DIR__ . '/../../vendor/autoload.php';

(function () {
    $loop              = \React\EventLoop\Factory::create();
    $newProcessFactory = new \idimsh\ParallelProcesses\NewProcessFactory();
    $processesConfig   = \idimsh\ParallelProcesses\BackgroundProcessesConfig::create();
    $logger            = new SimpleLog\Logger('php://stdout', basename(__FILE__));

    $writeStream = new \React\Stream\WritableResourceStream(STDOUT, $loop);
    $parallel    = new \idimsh\ParallelProcesses\ParallelCliProcesses(
        $processesConfig,
        $newProcessFactory,
        $loop,
        $logger
    );

    $processesConfig->setCallbackOnBeforeStart(new SampleOnStartLog());
    $processesConfig->setCallbackProcessStreamRead(new SampleOnReadDumpOutAndError($writeStream));
    $processesConfig->setCallbackProcessExit(new SampleOnExitStopAllIfError());

    $logger->info(sprintf('loop of type: %s', get_class($loop)));
    $parallel->execWithLoop([
        'failed ls' => SimpleCommand::fromString(
            'exec /bin/bash -c "ls -la /tmp/not-found"'
        )->setAsShellEscaped(true),

        'ls tmp' => SimpleCommand::fromString(
            'exec /bin/bash -c "ls -lad /tmp"'
        )->setAsShellEscaped(true),

        'long failed grep exec in bash' => SimpleCommand::fromString(
            'exec /bin/bash -c "sleep 3; grep --color -rHn \'random string not there\' /usr /var/"'
        )->setAsShellEscaped(true),
    ]);
    $loop->run();
})();
