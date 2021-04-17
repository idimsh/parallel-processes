<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest;

use idimsh\ParallelProcesses\Exception\ProcessRuntimeException;
use idimsh\ParallelProcesses\HackedProcess\BetterProcess;
use idimsh\ParallelProcesses\NewProcessFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class NewProcessFactoryTest extends TestCase
{
    /**
     * @var NewProcessFactory
     */
    protected $newProcessFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->newProcessFactory = new NewProcessFactory();
    }

    /**
     * @throws null
     */
    public function testNewProcess(): void
    {
        $command = ['/bin/bash', '-c', 'ls -la /home'];
        $cwd     = uniqid('cwd');
        $env     = [uniqid('env')];
        $input   = null;
        $timeout = null;
        $actual  = $this->newProcessFactory->newProcess($command, $cwd, $env, $input, $timeout);
        $this->assertInstanceOf(BetterProcess::class, $actual);
        $this->assertInstanceOf(Process::class, $actual);
        $this->assertInstanceOf(Process::class, $actual);
        $this->assertEquals($timeout, $actual->getTimeout());
        $this->assertEquals($env, $actual->getEnv());
        $this->assertEquals($input, $actual->getInput());
    }

    /**
     * @throws null
     */
    public function testNewProcessOnException(): void
    {
        $command = ['/bin/bash', '-c', 'ls -la /home'];
        $cwd     = uniqid('cwd');
        $env     = [uniqid('env')];
        $input   = null;
        $timeout = -10.3; // this will throw an exception
        $this->expectException(ProcessRuntimeException::class);
        $this->newProcessFactory->newProcess($command, $cwd, $env, $input, $timeout);
    }

    /**
     * @throws null
     */
    public function testNewProcessFromShellEscaped(): void
    {
        $command = '/bin/bash -c "ls -la /home"';
        $cwd     = uniqid('cwd');
        $env     = [uniqid('env')];
        $input   = null;
        $timeout = null;
        $actual  = $this->newProcessFactory->newProcessFromShellEscaped($command, $cwd, $env, $input, $timeout);
        $this->assertInstanceOf(BetterProcess::class, $actual);
        $this->assertInstanceOf(Process::class, $actual);
        $this->assertInstanceOf(Process::class, $actual);
        $this->assertEquals($timeout, $actual->getTimeout());
        $this->assertEquals($env, $actual->getEnv());
        $this->assertEquals($input, $actual->getInput());
    }

    /**
     * @throws null
     */
    public function testNewProcessFromShellEscapedOnException(): void
    {
        $command = '/bin/bash -c "ls -la /home"';
        $cwd     = uniqid('cwd');
        $env     = [uniqid('env')];
        $input   = null;
        $timeout = -10.3; // this will throw an exception
        $this->expectException(ProcessRuntimeException::class);
        $this->newProcessFactory->newProcessFromShellEscaped($command, $cwd, $env, $input, $timeout);
    }

    /**
     * @throws null
     */
    public function testGetPhpBinaryPath(): void
    {
        $actual = $this->newProcessFactory->getPhpBinaryPath();
        $this->assertThat($actual, $this->logicalOr($this->isNull(), $this->isType('string')));
    }
}
