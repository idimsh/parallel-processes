<?php

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

    $commandsArray = [
        'success grep'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
            'exec grep --color -rHn \'sudo\' /etc'
        )->setAsShellEscaped(true),

        'long failed grep exec no bash'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
            'exec grep --color -rHn \'random string not there\' /usr /var/'
        )->setAsShellEscaped(true),

        'failed ls'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
            'exec /bin/bash -c "ls -la /tmp/not-found"'
        )->setAsShellEscaped(true),


        'command not found' => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
            'exec /bin/bash -c command-not-found'
        )->setAsShellEscaped(true),


        'failed grep exec in bash' => (new \idimsh\ParallelProcesses\Command\SimpleCommand(
            ...[
                'exec',
                '/bin/bash',
                '-c',
                '"grep -rHni \'grer p\' /usr /var/"',
            ]
        ))->setAsShellEscaped(true),
    ];


    $processesConfig->setCallbackOnBeforeStart(new SampleOnStartLog());
    $processesConfig->setCallbackProcessStreamRead(new SampleOnReadDumpOutAndError($writeStream));
    $processesConfig->setCallbackProcessExit(new SampleOnExitStopAllIfError());

    $logger->info(sprintf('loop of type: %s', get_class($loop)));
    $parallel->execWithLoop($commandsArray);
    $loop->run();
})();
