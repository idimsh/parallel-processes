<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest;

use idimsh\ParallelProcesses\BackgroundProcessesConfig;
use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\NewProcessFactory;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use idimsh\PhpUnitTests\Unit\PHPUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\Process\Process;

final class ParallelCliProcessesTest extends PHPUnitTestCase
{
    /**
     * @var BackgroundProcessesConfig|MockObject
     */
    private $processesConfig;

    /**
     * @var NewProcessFactory|MockObject
     */
    private $newProcessFactory;

    /**
     * @var LoopInterface|MockObject
     */
    private $loop;

    /**
     * @var LoggerInterface|null|MockObject
     */
    private $logger;

    /**
     * @var ParallelCliProcesses|ParallelCliProcessesStub
     */
    private $parallelCliProcesses;

    /**
     * @var ParallelCliProcesses|MockObject
     */
    protected $selfDependency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processesConfig      = $this->createMock(BackgroundProcessesConfig::class);
        $this->newProcessFactory    = $this->createMock(NewProcessFactory::class);
        $this->loop                 = $this->createMock(LoopInterface::class);
        $this->logger               = null;
        $this->parallelCliProcesses = new ParallelCliProcessesStub(
            $this->processesConfig,
            $this->newProcessFactory,
            $this->loop,
            $this->logger
        );

        $this->selfDependency = $this->createMockWithProtectedMethods(ParallelCliProcesses::class);
    }

    /**
     * @throws null
     */
    public function testExecWithLoop(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);

        $commandsArray = [
            0     => $this->createMock(SimpleCommand::class),
            'two' => $this->createMock(SimpleCommand::class),
        ];
        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                0.01,
                $this->callback(
                    function (callable $realCallable) use ($commandsArray) {
                        $this->selfDependency->expects($this->once())->method('execInternal')
                            ->with($commandsArray);
                        $this->selfDependency->expects($this->once())->method('periodicCheckRunning');
                        $realCallable();
                        return true;
                    }
                )
            );
        $this->parallelCliProcesses->execWithLoop($commandsArray);
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::execInternal()
     * @throws null
     */
    public function testExecInternal(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandsArray = [
            0     => $this->createMock(SimpleCommand::class),
            'two' => $this->createMock(SimpleCommand::class),
        ];
        $this->selfDependency->expects($this->once())->method('isAnyRunning')->willReturn(false);
        $this->selfDependency->expects($this->once())->method('resetStatus')->with($commandsArray);
        $this->selfDependency->expects($this->once())
            ->method('nextLoop')
            ->with($commandsArray, 0, 2);
        $this->parallelCliProcesses->execInternal($commandsArray);
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::addStopCommandTimer()
     * @throws null
     */
    public function testAddStopCommandTimer(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandId = 'long process';
        $timeout   = random_int(1, 10) * lcg_value();
        $signal    = [random_int(0, 15), null][random_int(0, 1)];

        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                0.01,
                $this->callback(
                    function (callable $realCallable) use ($commandId, $timeout, $signal) {
                        $this->selfDependency->expects($this->once())->method('stopCommandInternal')
                            ->with($commandId, $timeout, $signal);
                        $realCallable();
                        return true;
                    }
                )
            );

        $this->parallelCliProcesses->addStopCommandTimer($commandId, $timeout, $signal);
    }

    /**
     * @throws null
     */
    public function testIsAnyRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('countRunning')->willReturn(0);
        $this->assertFalse($this->parallelCliProcesses->isAnyRunning());
    }

    /**
     * @throws null
     */
    public function testIsAnyRunningWhenTrue(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('countRunning')->willReturn(random_int(1, 2000));
        $this->assertTrue($this->parallelCliProcesses->isAnyRunning());
    }

    /**
     * @throws null
     */
    public function testCountRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('getRunningProcesses')
            ->willReturn(
                [
                    '1'            => $this->createMock(Process::class),
                    'long process' => $this->createMock(Process::class),
                    '3rd process'  => $this->createMock(Process::class),
                ]
            );

        $this->assertEquals(3, $this->parallelCliProcesses->countRunning());
    }

    /**
     * @throws null
     */
    public function testCountRunningZero(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('getRunningProcesses')
            ->willReturn([]);

        $this->assertEquals(0, $this->parallelCliProcesses->countRunning());
    }

    /**
     * @throws null
     */
    public function testGetLoop(): void
    {
        $this->assertSame($this->loop, $this->parallelCliProcesses->getLoop());
    }

    /**
     * @throws null
     */
    public function testGetLogger(): void
    {
        $this->assertSame($this->logger, $this->parallelCliProcesses->getLogger());
    }

    /**
     * @throws null
     */
    public function testStopAll(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $timeout          = random_int(1, 10) * lcg_value();
        $signal           = [random_int(0, 15), null][random_int(0, 1)];
        $process1         = $this->createMock(Process::class);
        $process2         = $this->createMock(Process::class);
        $process3         = $this->createMock(Process::class);
        $runningProcesses = [
            '1'            => $process1,
            'long process' => $process2,
            '3rd process'  => $process3,
        ];
        $this->selfDependency->expects($this->once())->method('getRunningProcesses')
            ->willReturn($runningProcesses);
        $this->selfDependency->expects($this->exactly(3))->method('addStopCommandTimer')
            ->with(
                $this->logicalOr(
                    '1',
                    'long process',
                    '3rd process'
                ),
                $timeout,
                $signal
            );

        $this->parallelCliProcesses->stopAll($timeout, $signal);
        // calling twice will not result in double calls
        $this->parallelCliProcesses->stopAll($timeout, $signal);
    }

    /**
     * @throws null
     */
    public function testStopAllWhenStopped(): void
    {
        $this->parallelCliProcesses->isStopped = true;
        $timeout                               = random_int(1, 10) * lcg_value();
        $signal                                = [random_int(0, 15), null][random_int(0, 1)];
        $this->loop->expects($this->never())->method('addTimer');
        $this->selfDependency->expects($this->never())->method('getRunningProcesses');
        $this->selfDependency->expects($this->never())->method('addStopCommandTimer');
        $this->parallelCliProcesses->stopAll($timeout, $signal);
    }

    /**
     * @throws null
     */
    public function testStopCommand(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $timeout   = random_int(1, 10) * lcg_value();
        $signal    = [random_int(0, 15), null][random_int(0, 1)];
        $commandId = 'long process';

        $this->selfDependency->expects($this->never())->method('getRunningProcesses');
        $this->selfDependency->expects($this->exactly(1))->method('addStopCommandTimer')
            ->with(
                $commandId,
                $timeout,
                $signal
            );

        $this->parallelCliProcesses->stopCommand($commandId, $timeout, $signal);
    }

    /**
     * @throws null
     */
    public function testStopCommandWhenNotRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->parallelCliProcesses->isStopped = true;
        $timeout                               = random_int(1, 10) * lcg_value();
        $signal                                = [random_int(0, 15), null][random_int(0, 1)];
        $commandId                             = 'long process';

        $this->selfDependency->expects($this->never())->method('getRunningProcesses');
        $this->selfDependency->expects($this->never())->method('addStopCommandTimer');
        $this->parallelCliProcesses->stopCommand($commandId, $timeout, $signal);
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::stopCommandInternal()
     * @throws null
     */
    public function testStopCommandInternal(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandId = 'long process';
        $timeout   = random_int(1, 10) * lcg_value();
        $signal    = [random_int(0, 15), null][random_int(0, 1)];
        $process1  = $this->createMock(Process::class);
        $command1  = $this->createMock(SimpleCommand::class);

        $this->parallelCliProcesses->processes     = [
            $commandId => $process1,
        ];
        $this->parallelCliProcesses->commandsArray = [
            $commandId => $command1,
        ];

        $process1->expects($this->once())->method('isRunning')->willReturn(true);
        $process1->expects($this->once())->method('stop')->with($timeout, $signal);

        $this->selfDependency->expects($this->once())
            ->method('processStopProcedure')
            ->with(
                $commandId,
                $process1
            );

        $this->parallelCliProcesses->stopCommandInternal($commandId, $timeout, $signal);
        $this->assertEquals([], $this->parallelCliProcesses->processes);
        $this->assertEquals(
            [$commandId => $command1],
            $this->parallelCliProcesses->commandsArray
        );
        $this->assertEquals(
            [$commandId => true],
            $this->parallelCliProcesses->commandsToStop
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::stopCommandInternal()
     * @throws null
     */
    public function testStopCommandInternalWhenProcessNotStarted(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandId                                 = 'long process';
        $timeout                                   = random_int(1, 10) * lcg_value();
        $signal                                    = [random_int(0, 15), null][random_int(0, 1)];
        $command                                   = $this->createMock(SimpleCommand::class);
        $process1                                  = $this->createMock(Process::class);
        $this->parallelCliProcesses->processes     = [
            'another command ID' => $process1,
        ];
        $this->parallelCliProcesses->commandsArray = [
            'another command ID' => $command,
        ];

        $this->selfDependency->expects($this->never())->method('processStopProcedure');
        $process1->expects($this->never())->method('isRunning');
        $process1->expects($this->never())->method('stop');

        $this->parallelCliProcesses->stopCommandInternal($commandId, $timeout, $signal);
        $this->assertEquals(
            ['another command ID' => $command],
            $this->parallelCliProcesses->commandsArray
        );
        $this->assertEquals(
            [$commandId => true],
            $this->parallelCliProcesses->commandsToStop
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::stopCommandInternal()
     * @throws null
     */
    public function testStopCommandInternalWhenProcessIsNotRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandId = 'long process';
        $timeout   = random_int(1, 10) * lcg_value();
        $signal    = [random_int(0, 15), null][random_int(0, 1)];
        $command   = $this->createMock(SimpleCommand::class);
        $process1  = $this->createMock(Process::class);

        $this->parallelCliProcesses->processes     = [
            $commandId => $process1,
        ];
        $this->parallelCliProcesses->commandsArray = [
            $commandId => $command,
        ];

        $process1->expects($this->once())->method('isRunning')->willReturn(false);
        $process1->expects($this->never())->method('stop');
        $this->selfDependency->expects($this->never())->method('processStopProcedure');

        $this->parallelCliProcesses->stopCommandInternal($commandId, $timeout, $signal);
        $this->assertEquals(
            [$commandId => $command],
            $this->parallelCliProcesses->commandsArray
        );
        $this->assertEquals(
            [$commandId => true],
            $this->parallelCliProcesses->commandsToStop
        );
    }


    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::nextLoop()
     * @throws null
     */
    public function testNextLoop(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandsArray = [
            0     => $this->createMock(SimpleCommand::class),
            'two' => $this->createMock(SimpleCommand::class),
        ];
        $alreadyRun    = 1;
        $totalCount    = 3;
        $this->selfDependency->expects($this->once())->method('execNext')
            ->with($commandsArray, $alreadyRun, $totalCount);
        $this->selfDependency->expects($this->once())->method('canRunMoreProcesses')->willReturn(true);
        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                0.01,
                $this->callback(
                    function (callable $realCallable) use (
                        &$commandsArray,
                        &$alreadyRun,
                        $totalCount
                    ) {
                        $this->selfDependency->expects($this->once())->method('nextLoop')
                            ->with($commandsArray, $alreadyRun, $totalCount);
                        $realCallable();
                        return true;
                    }
                )
            );
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'nextLoop',
            $params
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::nextLoop()
     * @throws null
     */
    public function testNextLoopWhenCanNotRunMoreProcesses(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandsArray = [
            0     => $this->createMock(SimpleCommand::class),
            'two' => $this->createMock(SimpleCommand::class),
        ];
        $alreadyRun    = 1;
        $totalCount    = 3;
        $this->selfDependency->expects($this->once())->method('execNext')
            ->with($commandsArray, $alreadyRun, $totalCount);
        $this->selfDependency->expects($this->once())->method('canRunMoreProcesses')->willReturn(false);
        $this->processesConfig->expects($this->once())->method('getProcessSleepMSec')->willReturn(70);
        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                70 / 1000,
                $this->callback(
                    function (callable $realCallable) use (
                        &$commandsArray,
                        &$alreadyRun,
                        $totalCount
                    ) {
                        $this->selfDependency->expects($this->once())->method('nextLoop')
                            ->with($commandsArray, $alreadyRun, $totalCount);
                        $realCallable();
                        return true;
                    }
                )
            );
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'nextLoop',
            $params
        );
    }


    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::nextLoop()
     * @throws null
     */
    public function testNextLoopWhenStopped(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->parallelCliProcesses->isStopped = true;
        $commandsArray                         = [
            0     => $this->createMock(SimpleCommand::class),
            'two' => $this->createMock(SimpleCommand::class),
        ];
        $alreadyRun                            = 1;
        $totalCount                            = 3;
        $this->selfDependency->expects($this->never())->method('execNext');
        $this->selfDependency->expects($this->never())->method('canRunMoreProcesses');
        $this->loop->expects($this->never())->method('addTimer');
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'nextLoop',
            $params
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::nextLoop()
     * @throws null
     */
    public function testNextLoopWhenCommandsArrayEmpty(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandsArray = [];
        $alreadyRun    = 1;
        $totalCount    = 3;
        $this->selfDependency->expects($this->never())->method('execNext');
        $this->selfDependency->expects($this->never())->method('canRunMoreProcesses');
        $this->loop->expects($this->never())->method('addTimer');
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'nextLoop',
            $params
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::execNext()
     * @throws null
     */
    public function testExecNext(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $command0      = $this->createMock(SimpleCommand::class);
        $commandTwo    = $this->createMock(SimpleCommand::class);
        $commandsArray = [
            0     => $command0,
            'two' => $commandTwo,
        ];
        $alreadyRun    = 1;
        $totalCount    = 3;
        $process       = $this->createMock(Process::class);
        $this->selfDependency->expects($this->once())->method('startBackgroundProcess')
            ->with($command0, '0')
            ->willReturn($process);
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'execNext',
            $params
        );
        $this->assertEquals(['two' => $commandTwo], $commandsArray);
        $this->assertEquals(2, $alreadyRun);
    }


    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::execNext()
     * @throws null
     */
    public function testExecNextWhenCommandMarkedForStop(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $command0                                   = $this->createMock(SimpleCommand::class);
        $commandTwo                                 = $this->createMock(SimpleCommand::class);
        $commandsArray                              = [
            0     => $command0,
            'two' => $commandTwo,
        ];
        $alreadyRun                                 = 1;
        $totalCount                                 = 3;
        $this->parallelCliProcesses->commandsToStop = [0 => true];
        $this->selfDependency->expects($this->never())->method('startBackgroundProcess');
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'execNext',
            $params
        );
        $this->assertEquals(['two' => $commandTwo], $commandsArray);
        $this->assertEquals(2, $alreadyRun);
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::execNext()
     * @throws null
     */
    public function testExecNextWhenCommandsArrayEmpty(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandsArray = [];
        $alreadyRun    = 1;
        $totalCount    = 3;
        $this->selfDependency->expects($this->never())->method('startBackgroundProcess');
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'execNext',
            $params
        );
        $this->assertEquals([], $commandsArray);
        $this->assertEquals(1, $alreadyRun);
    }


    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::execNext()
     * @throws null
     */
    public function testExecNextWhenProcessIsNull(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $commandTwo                                = $this->createMock(SimpleCommand::class);
        $commandsArray                             = [
            'two' => $commandTwo,
        ];
        $alreadyRun                                = -41;
        $totalCount                                = 50;
        $this->parallelCliProcesses->commandsArray = $commandsArray;
        $this->selfDependency->expects($this->once())->method('startBackgroundProcess')
            ->with($commandTwo, 'two')
            ->willReturn(null);
        $params = [
            &$commandsArray,
            &$alreadyRun,
            $totalCount,
        ];
        $this->invokeMethodParamsByReference(
            $this->parallelCliProcesses,
            'execNext',
            $params
        );
        $this->assertEquals([], $commandsArray);
        $this->assertEquals(-40, $alreadyRun);
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::periodicCheckRunning()
     * @throws null
     */
    public function testPeriodicCheckRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('isAnyRunning')
            ->willReturn(true);
        $processSleepMSec = random_int(0, 100);
        $this->processesConfig->expects($this->once())->method('getProcessSleepMSec')
            ->willReturn($processSleepMSec);
        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                $processSleepMSec / 1000,
                $this->callback(
                    function (callable $realCallable) {
                        $this->selfDependency->expects($this->once())->method('periodicCheckRunning');
                        $realCallable();
                        return true;
                    }
                )
            );

        $this->parallelCliProcesses->periodicCheckRunning();
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::periodicCheckRunning()
     * @throws null
     */
    public function testPeriodicCheckRunningWhenNothingIsRunning(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('isAnyRunning')
            ->willReturn(false);
        $this->processesConfig->expects($this->never())->method('getProcessSleepMSec');
        $this->loop->expects($this->never())->method('addTimer');

        $this->parallelCliProcesses->periodicCheckRunning();
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::periodicCheckRunning()
     * @throws null
     */
    public function testPeriodicCheckRunningWhenStopped(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->parallelCliProcesses->isStopped = true;
        $this->selfDependency->expects($this->never())->method('isAnyRunning');
        $this->processesConfig->expects($this->never())->method('getProcessSleepMSec');
        $this->loop->expects($this->never())->method('addTimer');

        $this->parallelCliProcesses->periodicCheckRunning();
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::getRunningProcesses()
     * @throws null
     */
    public function testGetRunningProcesses(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $process0   = $this->createMock(Process::class);
        $processTwo = $this->createMock(Process::class);
        $processes  = [
            '0'   => $process0,
            'two' => $processTwo,
        ];

        $this->parallelCliProcesses->processes = $processes;
        $process0->expects($this->once())->method('isRunning')
            ->willReturn(false);
        $processTwo->expects($this->once())->method('isRunning')
            ->willReturn(true);

        $this->selfDependency->expects($this->once())->method('processStopProcedure')
            ->with('0', $process0)
            ->willReturn(1);

        $actual = $this->parallelCliProcesses->getRunningProcesses();
        $this->assertEquals(
            [
                'two' => $processTwo,
            ],
            $actual
        );
    }

    /**
     * @covers \idimsh\ParallelProcesses\ParallelCliProcesses::processStopProcedure()
     * @throws null
     */
    public function testProcessStopProcedure(): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);

        $commandId  = '0';
        $process0   = $this->createMock(Process::class);
        $processTwo = $this->createMock(Process::class);
        $processes  = [
            $commandId => $process0,
            'two'      => $processTwo,
        ];

        $this->parallelCliProcesses->processes = $processes;

        $command0      = $this->createMock(SimpleCommand::class);
        $commandsArray = [
            $commandId => $command0,
        ];
        $this->setPropertyValue($this->parallelCliProcesses, 'commandsArray', $commandsArray);

        $exitCode = random_int(-100, 100);
        $process0->expects($this->once())->method('getExitCode')
            ->willReturn($exitCode);

        $isCallback1Called = false;
        $callback1         = function (
            $inProcess,
            $inExitCode,
            $inParallelCliProcess,
            $inCommand,
            $inCommandId
        ) use (
            &$isCallback1Called,
            $process0,
            $exitCode,
            $commandId,
            $command0
        ) {
            $isCallback1Called = true;
            $this->assertEquals($inProcess, $process0);
            $this->assertEquals($inExitCode, $exitCode);
            $this->assertEquals($inParallelCliProcess, $this->parallelCliProcesses);
            $this->assertEquals($inCommand, $command0);
            $this->assertEquals($inCommandId, $commandId);
        };
        $this->processesConfig->expects($this->exactly(2))->method('getCallbackProcessExit')
            ->willReturn($callback1);

        $this->loop->expects($this->once())->method('addTimer')
            ->with(
                0.01,
                $this->callback(
                    function (callable $realCallable) {
                        $realCallable();
                        return true;
                    }
                )
            );

        $actual = $this->parallelCliProcesses->processStopProcedure($commandId, $process0);
        $this->assertEquals($exitCode, $actual);
        $this->assertTrue($isCallback1Called);
    }


    /**
     * @dataProvider dataCanRunMoreProcesses
     * @covers       \idimsh\ParallelProcesses\ParallelCliProcesses::canRunMoreProcesses()
     * @param bool $expected
     * @param int  $countRunning
     * @param int  $simultaneousProcessesCount
     * @throws null
     */
    public function testCanRunMoreProcesses(
        bool $expected,
        int $countRunning,
        int $simultaneousProcessesCount
    ): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $this->selfDependency->expects($this->once())->method('countRunning')
            ->willReturn($countRunning);
        $this->processesConfig->expects($this->once())->method('getSimultaneousProcessesCount')
            ->willReturn($simultaneousProcessesCount);
        $this->assertEquals($expected, $this->parallelCliProcesses->canRunMoreProcesses());
    }

    public function dataCanRunMoreProcesses(): \Generator
    {
        yield '#00' => [false, 0, 0];
        yield '#01' => [true, 0, 1];
        yield '#02' => [false, 1, 0];
        yield '#03' => [false, 100, 50];
        yield '#04' => [true, 200, 400];
    }


    /**
     * @dataProvider dataStartBackgroundProcess
     * @covers       \idimsh\ParallelProcesses\ParallelCliProcesses::startBackgroundProcess()
     * @param bool $isAsShellEscapedReturn
     * @throws null
     */
    public function testStartBackgroundProcess(
        bool $isAsShellEscapedReturn
    ): void
    {
        $this->setSelfDependency($this->parallelCliProcesses);
        $command   = $this->createMock(SimpleCommand::class);
        $commandId = uniqid('commandId-');
        $command->expects($this->once())->method('isAsShellEscaped')
            ->willReturn($isAsShellEscapedReturn);
        $commandCwd = uniqid('cwd-');
        $commandEnv = null;
        if ($isAsShellEscapedReturn) {
            $commandString = 'exec /bin/bash';
            $command->expects($this->once())->method('getCommandString')->willReturn($commandString);
        }
        else {
            $commandParts = ['/bin/bash', '-c', 'ls -la /home'];
            $command->expects($this->once())->method('getCommandParts')->willReturn($commandParts);
        }
        $command->expects($this->once())->method('getCwd')->willReturn($commandCwd);
        $command->expects($this->once())->method('getEnv')->willReturn($commandEnv);
        $command->expects($this->once())->method('getInput')->willReturn(null);
        $command->expects($this->once())->method('getTimeout')->willReturn(null);
        $this->processesConfig->expects($this->once())->method('getProcessTimeoutSec')
            ->willReturn(234.0);
        $process = $this->createMock(Process::class);

        if ($isAsShellEscapedReturn) {
            $this->newProcessFactory->expects($this->once())->method('newProcessFromShellEscaped')
                ->with($commandString, $commandCwd, $commandEnv, null, 234.0)
                ->willReturn($process);
        }
        else {
            $this->newProcessFactory->expects($this->once())->method('newProcess')
                ->with($commandParts, $commandCwd, $commandEnv, null, 234.0)
                ->willReturn($process);
        }
        $isCbBeforeStartCalled = false;
        $cbBeforeStart         = function (
            $inParallelCliProcesses,
            $inProcess,
            $inCommand,
            $inCommandId
        ) use (
            &$isCbBeforeStartCalled,
            $process,
            $command,
            $commandId
        ) {
            $isCbBeforeStartCalled = true;
            $this->assertEquals($inParallelCliProcesses, $this->parallelCliProcesses);
            $this->assertEquals($inProcess, $process);
            $this->assertEquals($inCommand, $command);
            $this->assertEquals($inCommandId, $commandId);
        };
        $this->processesConfig->expects($this->once())->method('getCallbackOnBeforeStart')
            ->willReturn($cbBeforeStart);

        $isCbProcessStreamRead = false;
        $toBeSentType          = 'err';
        $toBeSentData          = 'command error output';
        $cbProcessStreamRead   = function (
            $inType,
            $inData,
            $inParallelCliProcesses,
            $inProcess,
            $inCommand,
            $inCommandId
        ) use (
            &$isCbProcessStreamRead,
            $toBeSentType,
            $toBeSentData,
            $process,
            $command,
            $commandId
        ) {
            $isCbProcessStreamRead = true;
            $this->assertEquals($inType, $toBeSentType);
            $this->assertEquals($inData, $toBeSentData);
            $this->assertEquals($inParallelCliProcesses, $this->parallelCliProcesses);
            $this->assertEquals($inProcess, $process);
            $this->assertEquals($inCommand, $command);
            $this->assertEquals($inCommandId, $commandId);
        };
        $this->processesConfig->expects($this->exactly(2))->method('getCallbackProcessStreamRead')
            ->willReturn($cbProcessStreamRead);

        $process->expects($this->once())->method('start')
            ->with(
                $this->callback(
                    function (callable $realCallback) use ($toBeSentType, $toBeSentData) {
                        $realCallback($toBeSentType, $toBeSentData);
                        return true;
                    }
                )
            );

        $actual = $this->parallelCliProcesses->startBackgroundProcess($command, $commandId);
        $this->assertEquals($process, $actual);
        $this->assertTrue($isCbBeforeStartCalled);
        $this->assertTrue($isCbProcessStreamRead);
        $this->assertEquals(
            [
                $commandId => $process,
            ],
            $this->parallelCliProcesses->processes
        );
    }

    public function dataStartBackgroundProcess(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @covers       \idimsh\ParallelProcesses\ParallelCliProcesses::resetStatus()
     * @throws null
     */
    public function testResetStatus(): void
    {
        $this->parallelCliProcesses->processes     = [
            '0' => $this->createMock(Process::class),
        ];
        $this->parallelCliProcesses->commandsArray = [
            '0' => $this->createMock(SimpleCommand::class),
            '1' => $this->createMock(SimpleCommand::class),
        ];
        $this->parallelCliProcesses->isStopped     = true;
        $commandTwo                                = $this->createMock(SimpleCommand::class);
        $commandsArray                             = [
            'two' => $commandTwo,
        ];

        $this->assertEquals(true, $this->parallelCliProcesses->isStopped);
        $this->assertNotEmpty($this->parallelCliProcesses->processes);
        $this->assertCount(2, $this->parallelCliProcesses->commandsArray);


        $this->parallelCliProcesses->resetStatus($commandsArray);
        $this->assertEquals(false, $this->parallelCliProcesses->isStopped);
        $this->assertEquals($commandsArray, $this->parallelCliProcesses->commandsArray);
        $this->assertEquals([], $this->parallelCliProcesses->processes);
    }
}
