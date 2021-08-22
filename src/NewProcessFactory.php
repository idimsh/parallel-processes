<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses;

use idimsh\ParallelProcesses\HackedProcess\BetterProcess;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class NewProcessFactory
{
    /**
     * @var string|null
     */
    protected $phpBinaryPath;

    /**
     * @param array       $command The command to run and its arguments listed as separate entries
     * @param null|string $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null  $env     The environment variables or null to use the same environment as the current PHP process
     * @param null        $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param float|null  $timeout The timeout in seconds or null to disable
     * @return \Symfony\Component\Process\Process
     * @throws Exception\ProcessRuntimeException|Exception\ParallelProcessThrowable
     */
    public function newProcess(
        array $command,
        ?string $cwd = null,
        ?array $env = null,
        $input = null,
        ?float $timeout = null
    ): Process {
        try {
            $instance = new BetterProcess(
                $command,
                $cwd,
                $env,
                $input,
                $timeout
            );
        } catch (\Symfony\Component\Process\Exception\ExceptionInterface $exception) {
            throw new Exception\ProcessRuntimeException(
                sprintf(
                    'Unable to create process, internal error: [%s]',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
        return $instance;
    }

    /**
     * @param string      $command The command line to pass to the shell of the OS
     * @param null|string $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null  $env     The environment variables or null to use the same environment as the current PHP process
     * @param null        $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param float|null  $timeout The timeout in seconds or null to disable
     * @return \Symfony\Component\Process\Process
     * @throws Exception\ProcessRuntimeException|Exception\ParallelProcessThrowable
     */
    public function newProcessFromShellEscaped(
        string $command,
        ?string $cwd = null,
        ?array $env = null,
        $input = null,
        ?float $timeout = null
    ): Process {
        try {
            $instance = new BetterProcess(
                $command,
                $cwd,
                $env,
                $input,
                $timeout
            );
        } catch (\Symfony\Component\Process\Exception\ExceptionInterface $exception) {
            throw new Exception\ProcessRuntimeException(
                sprintf(
                    'Unable to create process, internal error: [%s]',
                    $exception->getMessage()
                ),
                $exception->getCode(),
                $exception
            );
        }
        return $instance;
    }

    /**
     * @param bool $includeArgs
     * @return string|null The PHP executable path or null if it cannot be found
     */
    public function getPhpBinaryPath(bool $includeArgs = true): ?string
    {
        if ($this->phpBinaryPath === null) {
            $phpBinFinder        = new PhpExecutableFinder();
            $this->phpBinaryPath = $phpBinFinder->find($includeArgs);
            if ($this->phpBinaryPath === false) {
                $this->phpBinaryPath = null;
            }
        }
        return $this->phpBinaryPath;
    }
}
