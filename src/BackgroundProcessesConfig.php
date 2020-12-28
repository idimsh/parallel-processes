<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses;

class BackgroundProcessesConfig
{
    /**
     * The max number of processes to run in parallel
     *
     * @var int
     */
    protected $simultaneousProcessesCount = 10;

    /**
     * Process time out in seconds (float) before the process is forced to stop.
     *
     * @see \Symfony\Component\Process\Process::class
     *
     * @var float|null
     */
    protected $processTimeoutSec;

    /**
     * Sleep between pull times in milliseconds (int)
     *
     * @var int
     */
    protected $processSleepMSec = 70;

    /**
     * @var callable|null
     */
    protected $callbackProcessExit;

    /**
     * @var callable|null
     */
    protected $callbackProcessStreamRead;

    /**
     * @var callable|null
     */
    protected $callbackOnBeforeStart;


    public static function create(): self
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return new static(...func_get_args());
    }

    /**
     * @return int The max number of processes to run in parallel
     */
    public function getSimultaneousProcessesCount(): int
    {
        return $this->simultaneousProcessesCount;
    }

    /**
     * @param int $simultaneousProcessesCount The max number of processes to run in parallel
     * @return BackgroundProcessesConfig
     */
    public function setSimultaneousProcessesCount(int $simultaneousProcessesCount): self
    {
        $this->simultaneousProcessesCount = $simultaneousProcessesCount;
        return $this;
    }

    /**
     * @return float|null Process time out in seconds (float) before the process is forced to stop.
     */
    public function getProcessTimeoutSec(): ?float
    {
        return $this->processTimeoutSec;
    }

    /**
     * @param float|null $processTimeoutSec Process time out in seconds (float) before the process is forced to stop.
     * @return static
     */
    public function setProcessTimeoutSec(?float $processTimeoutSec): self
    {
        $this->processTimeoutSec = $processTimeoutSec;
        return $this;
    }

    /**
     * @return int Sleep between pull times in milliseconds
     */
    public function getProcessSleepMSec(): int
    {
        return $this->processSleepMSec;
    }

    /**
     * @param int $processSleepMSec Sleep between pull times in milliseconds
     * @return static
     */
    public function setProcessSleepMSec(int $processSleepMSec): self
    {
        $this->processSleepMSec = $processSleepMSec;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getCallbackProcessExit(): ?callable
    {
        return $this->callbackProcessExit;
    }

    /**
     * @param callable|null $callbackProcessExit with signature like:
     *
     * function(
     *      \Symfony\Component\Process\Process $process,
     *      ?int $exitCode,
     *      \idimsh\ParallelProcesses\ParallelCliProcesses $parallelCliProcesses,
     *      \idimsh\ParallelProcesses\Command\Command $command,
     *      string $commandId
     * ): void
     * @return static
     */
    public function setCallbackProcessExit(?callable $callbackProcessExit): self
    {
        $this->callbackProcessExit = $callbackProcessExit;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getCallbackProcessStreamRead(): ?callable
    {
        return $this->callbackProcessStreamRead;
    }

    /**
     * @param callable|null $callbackProcessStreamRead with signature like:
     *
     * function(
     *     mixed|string $type, // one of \Symfony\Component\Process\Process::OUT, \Symfony\Component\Process\Process::ERR
     *     mixed $data
     *     \idimsh\ParallelProcesses\ParallelCliProcesses $parallelCliProcesses,
     *     \Symfony\Component\Process\Process $process,
     *     \idimsh\ParallelProcesses\Command\Command $command,
     *     string $commandId
     * ): void
     * @return static
     */
    public function setCallbackProcessStreamRead(?callable $callbackProcessStreamRead): self
    {
        $this->callbackProcessStreamRead = $callbackProcessStreamRead;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getCallbackOnBeforeStart(): ?callable
    {
        return $this->callbackOnBeforeStart;
    }

    /**
     * @param callable|null $callbackOnBeforeStart with signature like:
     *
     * function(
     *     \idimsh\ParallelProcesses\ParallelCliProcesses $parallelCliProcesses,
     *     \Symfony\Component\Process\Process $process,
     *     \idimsh\ParallelProcesses\Command\Command $command,
     *     string $commandId
     * ): void
     * @return static
     */
    public function setCallbackOnBeforeStart(?callable $callbackOnBeforeStart): self
    {
        $this->callbackOnBeforeStart = $callbackOnBeforeStart;
        return $this;
    }
}
