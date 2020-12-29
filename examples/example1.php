<?php
require_once __DIR__ . '/../vendor/autoload.php';

$loop              = \React\EventLoop\Factory::create();
$newProcessFactory = new \idimsh\ParallelProcesses\NewProcessFactory();
$processesConfig   = \idimsh\ParallelProcesses\BackgroundProcessesConfig::create();

$parallel    = new \idimsh\ParallelProcesses\ParallelCliProcesses(
    $processesConfig,
    $newProcessFactory,
    $loop
);
$parallel->execWithLoop([
    'failed ls'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
        'exec /bin/bash -c "ls -la /tmp/not-found"'
    )->setAsShellEscaped(true),

    'long failed grep exec in bash'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
        'exec /bin/bash -c "sleep 3; grep --color -rHn \'random string not there\' /usr /var/"'
    )->setAsShellEscaped(true),
]);
$loop->run();
