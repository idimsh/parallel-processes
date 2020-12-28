<?php
declare(strict_types=1);

namespace idimsh\ParallelProcesses\Command;

use Clue\Arguments;
use idimsh\ParallelProcesses\Exception;
use function implode;

class SimpleCommand
{
    /**
     * @var string[]
     */
    protected $arguments;

    /**
     * @var bool
     */
    protected $asShellEscaped = false;

    /**
     * @var string|null
     */
    protected $cwd = null;

    /**
     * @var array|null
     */
    protected $env = null;

    /**
     * @var mixed
     */
    protected $input;

    /**
     * @var float|null
     */
    protected $timeout;

    public function __construct(string ...$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @param string $input
     * @return static
     */
    public static function fromString(string $input): self
    {
        return new self(...preg_split('# +#', $input));
    }

    /**
     * @return string[]
     */
    public function getCommandParts(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getCommandString(): string
    {
        return implode(' ', $this->arguments);
    }

    /**
     * @return bool
     */
    public function isAsShellEscaped(): bool
    {
        return $this->asShellEscaped;
    }

    /**
     * If set to TRUE, the command line parts will not be passed to { @link \Symfony\Component\Process\Process::escapeArgument() }
     * and are assumed as already escaped.
     *
     * @param bool $asShellEscaped
     * @return self
     */
    public function setAsShellEscaped(bool $asShellEscaped): self
    {
        $this->asShellEscaped = $asShellEscaped;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCwd(): ?string
    {
        return $this->cwd;
    }

    /**
     * @param string|null $cwd
     * @return static
     */
    public function setCwd(?string $cwd): self
    {
        $this->cwd = $cwd;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getEnv(): ?array
    {
        return $this->env;
    }

    /**
     * @param array|null $env
     * @return static
     */
    public function setEnv(?array $env): self
    {
        $this->env = $env;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * @param mixed $input
     * @return static
     */
    public function setInput($input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getTimeout(): ?float
    {
        return $this->timeout;
    }

    /**
     * @param float|null $timeout
     * @return static
     */
    public function setTimeout(?float $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
}
