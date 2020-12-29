<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest;

use idimsh\ParallelProcesses\BackgroundProcessesConfig;
use PHPUnit\Framework\TestCase;

final class BackgroundProcessesConfigTest extends TestCase
{
    /**
     * @var BackgroundProcessesConfig
     */
    protected $backgroundProcessesConfig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backgroundProcessesConfig = new BackgroundProcessesConfig();
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf(BackgroundProcessesConfig::class, BackgroundProcessesConfig::create());
    }

    public function testGetSimultaneousProcessesCount(): void
    {
        $this->assertSame(10, $this->backgroundProcessesConfig->getSimultaneousProcessesCount());
    }

    public function testSetSimultaneousProcessesCount(): void
    {
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setSimultaneousProcessesCount(50));
        $this->assertSame(50, $this->backgroundProcessesConfig->getSimultaneousProcessesCount());
    }

    public function testGetProcessTimeoutSec(): void
    {
        $this->assertSame(null, $this->backgroundProcessesConfig->getProcessTimeoutSec());
    }

    public function testSetProcessTimeoutSec(): void
    {
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setProcessTimeoutSec(123.4));
        $this->assertSame(123.4, $this->backgroundProcessesConfig->getProcessTimeoutSec());
    }

    public function testGetProcessSleepMSec(): void
    {
        $this->assertSame(70, $this->backgroundProcessesConfig->getProcessSleepMSec());
    }

    public function testSetProcessSleepMSec(): void
    {
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setProcessSleepMSec(234));
        $this->assertSame(234, $this->backgroundProcessesConfig->getProcessSleepMSec());
    }

    public function testGetCallbackProcessExit(): void
    {
        $this->assertSame(null, $this->backgroundProcessesConfig->getCallbackProcessExit());
    }

    public function testSetCallbackProcessExit(): void
    {
        $cb = function () {
        };
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setCallbackProcessExit($cb));
        $this->assertSame($cb, $this->backgroundProcessesConfig->getCallbackProcessExit());
    }

    public function testGetCallbackProcessStreamRead(): void
    {
        $this->assertSame(null, $this->backgroundProcessesConfig->getCallbackProcessStreamRead());
    }

    public function testSetCallbackProcessStreamRead(): void
    {
        $cb = function () {
        };
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setCallbackProcessStreamRead($cb));
        $this->assertSame($cb, $this->backgroundProcessesConfig->getCallbackProcessStreamRead());
    }

    public function testGetCallbackOnBeforeStart(): void
    {
        $this->assertSame(null, $this->backgroundProcessesConfig->getCallbackOnBeforeStart());
    }

    public function testSetCallbackOnBeforeStart(): void
    {
        $cb = function () {
        };
        $this->assertSame($this->backgroundProcessesConfig, $this->backgroundProcessesConfig->setCallbackOnBeforeStart($cb));
        $this->assertSame($cb, $this->backgroundProcessesConfig->getCallbackOnBeforeStart());
    }
}
