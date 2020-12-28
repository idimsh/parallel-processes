<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest;

use idimsh\ParallelProcesses\ParallelCliProcesses;
use idimsh\PhpUnitTests\Traits\PrivateMethodsTrait;
use idimsh\PhpUnitTests\Traits\PrivatePropertiesTrait;

/**
 * @method stopAllInternal()
 * @method nextLoop()
 * @method periodicCheckRunning()
 * @method getRunningProcesses()
 * @method processStopProcedure()
 * @method canRunMoreProcesses()
 * @method startBackgroundProcess($command, $commandId)
 * @method resetStatus(array $commandsArray)
 *
 * @property array $processes
 * @property bool  $isStopped
 * @property bool  $commandsArray
 */
final class ParallelCliProcessesStub extends ParallelCliProcesses
{
    use PrivateMethodsTrait;
    use PrivatePropertiesTrait;
}
