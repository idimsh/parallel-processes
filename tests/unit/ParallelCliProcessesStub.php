<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use idimsh\ParallelProcesses\ParallelCliProcesses;
use idimsh\PhpUnitTests\Traits\PrivateMethodsTrait;
use idimsh\PhpUnitTests\Traits\PrivatePropertiesTrait;
use idimsh\PhpUnitTests\Traits\PropertiesAndMethodsReflectionTrait;
use Symfony\Component\Process\Process;

/**
 * @method execInternal(array $commandsArray)
 * @method stopCommandInternal(string $commandId, float $timeout = 10, int $signal = null)
 * @method addStopCommandTimer(string $commandId, float $timeout = 10, int $signal = null)
 * @method nextLoop()
 * @method periodicCheckRunning()
 * @method getRunningProcesses()
 * @method processStopProcedure()
 * @method canRunMoreProcesses()
 * @method startBackgroundProcess($command, $commandId)
 * @method resetStatus(array $commandsArray)
 *
 * @property Process[]       $processes
 * @property bool            $isStopped
 * @property SimpleCommand[] $commandsArray
 * @property bool[]          $commandsToStop
 */
final class ParallelCliProcessesStub extends ParallelCliProcesses
{
    use PrivateMethodsTrait;
    use PrivatePropertiesTrait;
}
