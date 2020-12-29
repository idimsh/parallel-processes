<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses\HackedProcess;

use Symfony\Component\Process\Process;

/**
 * This is called to control a bug (or might not be a bug) in { @link proc_get_status() }
 * Where two or more sequential calls to it (if the process is exited) will not return
 * the correct 'exitcode' but for the first call only. And this does happen if the process is
 * started with 'exec' as Symfony original process do sometimes.
 *
 * This class solves the problem by storing the valid exit code from the first call (which is
 * >= 0) and ignore it for later calls.
 *
 */
class BetterProcess extends Process
{
    /**
     * @var \ReflectionProperty
     */
    protected $propProcess;

    /**
     * @var \ReflectionProperty
     */
    protected $propProcessInformation;

    /**
     * @var \ReflectionProperty
     */
    protected $propFallbackStatus;

    /**
     * @var \ReflectionProperty
     */
    protected $propStatus;

    /**
     * @var \ReflectionMethod
     */
    protected $methodReadPipes;

    /**
     * @var \ReflectionMethod
     */
    protected $methodClose;

    /**
     * @var int|null
     */
    protected $previousRealExitCode = null;

    /**
     * HackedProcess constructor.
     *
     * @param array       $command
     * @param null|string $cwd
     * @param array|null  $env
     * @param mixed|null  $input
     * @param float|null  $timeout
     * @throws \Symfony\Component\Process\Exception\ExceptionInterface
     */
    public function __construct(array $command, ?string $cwd = null, ?array $env = null, $input = null, ?float $timeout = 60.0)
    {
        parent::__construct($command, $cwd, $env, $input, $timeout);

        $this->propStatus = new \ReflectionProperty(Process::class, 'status');
        $this->propStatus->setAccessible(true);
        $this->propProcess = new \ReflectionProperty(Process::class, 'process');
        $this->propProcess->setAccessible(true);
        $this->propProcessInformation = new \ReflectionProperty(Process::class, 'processInformation');
        $this->propProcessInformation->setAccessible(true);
        $this->propFallbackStatus = new \ReflectionProperty(Process::class, 'fallbackStatus');
        $this->propFallbackStatus->setAccessible(true);
        $this->methodReadPipes = new \ReflectionMethod(Process::class, 'readPipes');
        $this->methodReadPipes->setAccessible(true);
        $this->methodClose = new \ReflectionMethod(Process::class, 'close');
        $this->methodClose->setAccessible(true);
    }

    protected function updateStatus(bool $blocking)
    {
        if (self::STATUS_STARTED !== $this->propStatus->getValue($this)) {
            return;
        }

        $processInformation = proc_get_status($this->propProcess->getValue($this));
        if ($this->previousRealExitCode === null && ($processInformation['exitcode'] ?? -1) >= 0) {
            $this->previousRealExitCode = $processInformation['exitcode'];
        }
        if ($this->previousRealExitCode !== null) {
            $processInformation['exitcode'] = $this->previousRealExitCode;
        }

        $this->propProcessInformation->setValue($this, $processInformation);
        $running = $processInformation['running'];

        $this->methodReadPipes->invoke($this, $running && $blocking, '\\' !== \DIRECTORY_SEPARATOR || !$running);

        if ($this->propFallbackStatus->getValue($this) && $this->isSigchildEnabled()) {
            $processInformation = $this->propFallbackStatus->getValue($this) + $processInformation;
            $this->propProcessInformation->setValue($this, $processInformation);
        }

        if (!$running) {
            $this->methodClose->invoke($this);
        }
    }

    /**
     * @param callable|null $callback
     * @param array         $env
     * @throws \Symfony\Component\Process\Exception\LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function start(?callable $callback = null, array $env = []): void
    {
        if (!$this->isRunning()) {
            $this->previousRealExitCode = null;
        }
        parent::start($callback, $env);
    }
}
