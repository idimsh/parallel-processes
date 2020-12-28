<?php
declare(strict_types=1);

namespace idimsh\ParallelProcessesUnitTest\Command;

use idimsh\ParallelProcesses\Command\SimpleCommand;
use PHPUnit\Framework\TestCase;

class SimpleCommandTest extends TestCase
{
    /**
     * @var SimpleCommand
     */
    protected $simpleCommand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleCommand = new SimpleCommand();
    }

    /**
     * @throws null
     */
    public function testFromString(): void
    {
        $actual = $this->simpleCommand::fromString('command param     with');
        $this->assertInstanceOf(SimpleCommand::class, $actual);
        $this->assertEquals(
            [
                'command',
                'param',
                'with',
            ],
            $actual->getCommandParts()
        );
    }

    /**
     * @throws null
     */
    public function testGetCommandParts(): void
    {
        $commandParts = [
            'bash',
            '-c',
            'command',
        ];

        $simpleCommand = new SimpleCommand(...$commandParts);
        $this->assertSame($commandParts, $simpleCommand->getCommandParts());
        $this->assertSame('bash -c command', $simpleCommand->getCommandString());
    }

    /**
     * @throws null
     */
    public function testIsAsShellEscaped(): void
    {
        $this->assertSame(false, $this->simpleCommand->isAsShellEscaped());
    }

    /**
     * @throws null
     */
    public function testSetAsShellEscaped(): void
    {
        $this->assertSame($this->simpleCommand, $this->simpleCommand->setAsShellEscaped(true));
        $this->assertSame(true, $this->simpleCommand->isAsShellEscaped());
    }

    /**
     * @throws null
     */
    public function testGetCwd(): void
    {
        $this->assertSame(null, $this->simpleCommand->getCwd());
    }

    /**
     * @throws null
     */
    public function testSetCwd(): void
    {
        $this->assertSame($this->simpleCommand, $this->simpleCommand->setCwd('current working dir'));
        $this->assertSame('current working dir', $this->simpleCommand->getCwd());
    }

    /**
     * @throws null
     */
    public function testGetEnv(): void
    {
        $this->assertSame(null, $this->simpleCommand->getEnv());
    }

    /**
     * @throws null
     */
    public function testSetEnv(): void
    {
        $this->assertSame($this->simpleCommand, $this->simpleCommand->setEnv(['HOME', '/www']));
        $this->assertSame(['HOME', '/www'], $this->simpleCommand->getEnv());
    }

    /**
     * @throws null
     */
    public function testGetInput(): void
    {
        $this->assertSame(null, $this->simpleCommand->getInput());
    }

    /**
     * @throws null
     */
    public function testSetInput(): void
    {
        $this->assertSame($this->simpleCommand, $this->simpleCommand->setInput(STDIN));
        $this->assertSame(STDIN, $this->simpleCommand->getInput());
    }

    /**
     * @throws null
     */
    public function testGetTimeout(): void
    {
        $this->assertSame(null, $this->simpleCommand->getTimeout());
    }

    /**
     * @throws null
     */
    public function testSetTimeout(): void
    {
        $this->assertSame($this->simpleCommand, $this->simpleCommand->setTimeout(11.2));
        $this->assertSame(11.2, $this->simpleCommand->getTimeout());
    }
}
