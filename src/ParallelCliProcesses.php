<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\Exception\ProcessRuntimeException;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Process;

class ParallelCliProcesses
{
    /**
     * @var BackgroundProcessesConfig
     */
    protected $processesConfig;

    /**
     * @var NewProcessFactory
     */
    protected $newProcessFactory;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /**
     * @var self
     */
    protected $selfDependency;

    /**
     * @var Process\Process[]
     */
    protected $processes = [];

    /**
     * @var SimpleCommand[]
     */
    protected $commandsArray = [];

    /**
     * @var bool
     */
    protected $isSignalRegistered = false;

    /**
     * @var bool
     */
    protected $isStopped;

    public function __construct(
        BackgroundProcessesConfig $backgroundProcessesConfig,
        NewProcessFactory $newProcessFactory,
        LoopInterface $loop,
        ?LoggerInterface $logger
    )
    {
        $this->processesConfig   = $backgroundProcessesConfig;
        $this->newProcessFactory = $newProcessFactory;
        $this->loop              = $loop;
        $this->logger            = $logger;

        $this->selfDependency = $this;
    }

    public function __clone()
    {
        $this->selfDependency = $this;
    }

    public function __wakeup()
    {
        $this->selfDependency = $this;
    }


    /**
     * @param SimpleCommand[] $commandsArray
     */
    public function execWithLoop(
        array $commandsArray
    ): void
    {
        $this->loop->addTimer(
            0.01,
            function () use ($commandsArray) {
                $this->selfDependency->exec($commandsArray);
                $this->selfDependency->periodicCheckRunning();
            }
        );
    }

    /**
     * @param SimpleCommand[] $commandsArray
     * @throws Exception\ParallelProcessThrowable
     * @throws Exception\ProcessRuntimeException
     */
    public function exec(
        array $commandsArray
    ): void
    {
        if ($this->selfDependency->isAnyRunning()) {
            throw new ProcessRuntimeException(
                'Can\'t run new processes while current processes not exited'
            );
        }
        $this->selfDependency->resetStatus($commandsArray);
        $totalCount = count($commandsArray);
        $alreadyRun = 0;
        $this->selfDependency->nextLoop(
            $commandsArray,
            $alreadyRun,
            $totalCount
        );
    }

    public function isAnyRunning(): bool
    {
        return $this->selfDependency->countRunning() > 0;
    }

    public function countRunning(): int
    {
        return count($this->selfDependency->getRunningProcesses());
    }

    public function getLoop(): LoopInterface
    {
        return $this->loop;
    }

    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Stop all processes currently running and cease to start those which are not yet run.
     *
     * @param float    $timeout
     * @param int|null $signal
     */
    public function stopAll(float $timeout = 10.0, ?int $signal = null): void
    {
        if ($this->isStopped) {
            return;
        }
        $this->isStopped = true;
        $this->loop->addTimer(
            0.01,
            function () use ($timeout, $signal) {
                $this->selfDependency->stopAllInternal($timeout, $signal);
            }
        );
    }


    /**
     * This will try to stop sequentially, process by process, and wait max of $timeout for each to stop.
     *
     * @todo find a way to do this in parallel
     * @param float    $timeout
     * @param int|null $signal
     */
    protected function stopAllInternal(float $timeout = 10, int $signal = null): void
    {
        foreach ($this->selfDependency->getRunningProcesses() as $commandId => $process) {
            if (!$process->isRunning()) {
                continue;
            }
            $this->logger
            && $this->logger->debug(
                sprintf(
                    'stopping commandId: [%s], PID: [%d]',
                    $commandId,
                    $process->getPid()
                )
            );
            unset($this->processes[$commandId]);
            $process->stop($timeout);
            $this->selfDependency->processStopProcedure((string) $commandId, $process);
        }
    }

    /**
     * @param array $commandsArray
     * @param int   $alreadyRun
     * @param int   $totalCount
     * @throws Exception\ParallelProcessThrowable
     * @throws ProcessRuntimeException
     */
    protected function nextLoop(
        array &$commandsArray,
        int &$alreadyRun,
        int $totalCount
    ): void
    {
        if ($this->isStopped) {
            if ($this->logger && count($commandsArray) > 0) {
                $this->logger->info(
                    sprintf(
                        '[%d] command will not be started since stop is requested',
                        count($commandsArray)
                    )
                );
            }
            return;
        }
        if (count($commandsArray) === 0) {
            return;
        }
        $this->selfDependency->execNext($commandsArray, $alreadyRun, $totalCount);
        $this->loop->addTimer(
            $this->selfDependency->canRunMoreProcesses() ? 0.01 : $this->processesConfig->getProcessSleepMSec() / 1000,
            function () use (&$commandsArray, &$alreadyRun, $totalCount) {
                $this->selfDependency->nextLoop(
                    $commandsArray,
                    $alreadyRun,
                    $totalCount
                );
            }
        );
    }

    /**
     * @param array $commandsArray
     * @param int   $alreadyRun
     * @param int   $totalCount
     * @throws Exception\ParallelProcessThrowable
     * @throws ProcessRuntimeException
     */
    protected function execNext(
        array &$commandsArray,
        int &$alreadyRun,
        int $totalCount
    ): void
    {
        $command = reset($commandsArray);
        if (!$command) {
            return;
        }
        $commandId = (string) key($commandsArray);
        array_shift($commandsArray);
        $process = $this->selfDependency->startBackgroundProcess(
            $command,
            $commandId
        );
        $alreadyRun++;
        if ($process === null) {
            // startBackgroundProcess has already logged the failure
            return;
        }
        if ($this->logger) {
            $this->logger->debug(
                sprintf(
                    'Started commandId: [%s], command: [%s], PID: [%d], remaining processes to run: [%d]',
                    $commandId,
                    $process->getCommandLine(),
                    $process->getPid(),
                    $totalCount - $alreadyRun
                )
            );
        }
    }


    protected function periodicCheckRunning(): void
    {
        if (!$this->isStopped) {
            if ($this->selfDependency->isAnyRunning()) {
                $this->loop->addTimer(
                    $this->processesConfig->getProcessSleepMSec() / 1000,
                    function () {
                        $this->selfDependency->periodicCheckRunning();
                    }
                );
            }
            else {
                if ($this->logger) {
                    $this->logger->info('No more processes are running');
                }
            }
        }
    }

    /**
     * @return Process\Process[]
     */
    protected function getRunningProcesses(): array
    {
        $processes        = [];
        $currentProcesses = $this->processes;
        foreach ($currentProcesses as $commandId => $process) {
            $commandId = (string) $commandId;
            if ($process->isRunning()) {
                $processes[$commandId] = $process;
            }
            else {
                $exitCode = $this->selfDependency->processStopProcedure($commandId, $process);
                if ($this->logger) {
                    $this->logger->debug(
                        sprintf('command ID: [%s] is not running, exit code: [%s]', $commandId, var_export($exitCode, true))
                    );
                }
            }
        }
        return $processes;
    }

    protected function processStopProcedure(string $commandId, Process\Process $process): ?int
    {
        unset($this->processes[$commandId]);

        try {
            $exitCode = $process->getExitCode();
        }
        catch (Process\Exception\RuntimeException $exception) {
            $exitCode = null;
        }
        if ($this->processesConfig->getCallbackProcessExit()) {
            $this->loop->addTimer(
                0.01,
                function () use (&$process, $exitCode, $commandId) {
                    $this->processesConfig->getCallbackProcessExit()(
                        $process,
                        $exitCode,
                        $this,
                        $this->commandsArray[$commandId],
                        $commandId
                    );
                }
            );
        }
        return $exitCode;
    }

    protected function canRunMoreProcesses(): bool
    {
        return $this->selfDependency->countRunning()
            < $this->processesConfig->getSimultaneousProcessesCount();
    }

    /**
     * @param SimpleCommand $command
     * @param string        $commandId
     * @return Process\Process|null
     * @throws Exception\ParallelProcessThrowable|ProcessRuntimeException
     */
    protected function startBackgroundProcess(SimpleCommand $command, string $commandId): ?Process\Process
    {
        try {
            if ($command->isAsShellEscaped()) {
                $process = $this->newProcessFactory->newProcessFromShellEscaped(
                    $command->getCommandString(),
                    $command->getCwd(),
                    $command->getEnv(),
                    $command->getInput(),
                    $command->getTimeout() ?? $this->processesConfig->getProcessTimeoutSec()
                );
            }
            else {
                $process = $this->newProcessFactory->newProcess(
                    $command->getCommandParts(),
                    $command->getCwd(),
                    $command->getEnv(),
                    $command->getInput(),
                    $command->getTimeout() ?? $this->processesConfig->getProcessTimeoutSec()
                );
            }
            $callback = $this->processesConfig->getCallbackOnBeforeStart();
            if ($callback) {
                $callback($this, $process, $command, $commandId);
            }
            if ($this->logger) {
                $this->logger->debug(
                    sprintf(
                        'starting commandId: [%s] command: [%s]',
                        $commandId,
                        $process->getCommandLine()
                    )
                );
            }
            $callback = !$this->processesConfig->getCallbackProcessStreamRead()
                ? null
                : function ($type, $data) use (&$process, $command, $commandId) {
                    $this->processesConfig->getCallbackProcessStreamRead()($type, $data, $this, $process, $command, $commandId);
                };
            $process->start($callback);
        }
        catch (Process\Exception\ExceptionInterface $exception) {
            if ($this->logger) {
                $this->logger->error(
                    sprintf(
                        '%s: Failed to start process with commandId: [%s] command: [%s], got error of type: [%s] and message: [%s]',
                        __METHOD__,
                        $commandId,
                        $process->getCommandLine(),
                        get_class($exception),
                        $exception->getMessage()
                    )
                );
            }
            return null;
        }
        $this->processes[$commandId] = $process;
        return $process;
    }

    protected function resetStatus(array $commandsArray): void
    {
        $this->processes     = [];
        $this->commandsArray = $commandsArray;
        $this->isStopped     = false;
    }
}
