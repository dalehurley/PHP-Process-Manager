<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager;

use DaleHurley\ProcessManager\Exception\ProcessStartException;

/**
 * Represents a single running process.
 */
final class Process
{
    /** @var resource|false */
    private mixed $resource;

    /** @var array<int, resource> */
    private array $pipes = [];

    private int $startTime;

    private bool $closed = false;

    /**
     * @param array<int, string> $arguments Command-line arguments for the process
     * @param array<string, string> $environment Environment variables for the process
     * @throws ProcessStartException If the process fails to start
     */
    public function __construct(
        public readonly string $executable,
        public readonly string $script,
        public readonly string $workingDirectory = '',
        public readonly int $maxExecutionTime = 300,
        public readonly array $arguments = [],
        array $environment = []
    ) {
        $this->start($environment);
    }

    /**
     * Check if the process is still running.
     */
    public function isRunning(): bool
    {
        if ($this->closed || !is_resource($this->resource)) {
            return false;
        }

        $status = proc_get_status($this->resource);
        return $status['running'];
    }

    /**
     * Check if the process has exceeded its maximum execution time.
     */
    public function hasExceededTimeout(): bool
    {
        return (time() - $this->startTime) > $this->maxExecutionTime;
    }

    /**
     * Get the elapsed time in seconds since the process started.
     */
    public function getElapsedTime(): int
    {
        return time() - $this->startTime;
    }

    /**
     * Get the process ID if available.
     */
    public function getPid(): ?int
    {
        if ($this->closed || !is_resource($this->resource)) {
            return null;
        }

        $status = proc_get_status($this->resource);
        return $status['pid'];
    }

    /**
     * Get the exit code of the process (if completed).
     */
    public function getExitCode(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $status = proc_get_status($this->resource);
        if ($status['running']) {
            return null;
        }

        return $status['exitcode'];
    }

    /**
     * Read output from the process (stdout).
     */
    public function getOutput(): string
    {
        if (!isset($this->pipes[1]) || !is_resource($this->pipes[1])) {
            return '';
        }

        stream_set_blocking($this->pipes[1], false);
        return stream_get_contents($this->pipes[1]) ?: '';
    }

    /**
     * Read error output from the process (stderr).
     */
    public function getErrorOutput(): string
    {
        if (!isset($this->pipes[2]) || !is_resource($this->pipes[2])) {
            return '';
        }

        stream_set_blocking($this->pipes[2], false);
        return stream_get_contents($this->pipes[2]) ?: '';
    }

    /**
     * Terminate the process forcefully.
     */
    public function terminate(int $signal = 15): bool
    {
        if ($this->closed || !is_resource($this->resource)) {
            return false;
        }

        return proc_terminate($this->resource, $signal);
    }

    /**
     * Close the process and clean up resources.
     */
    public function close(): int
    {
        if ($this->closed) {
            return -1;
        }

        $this->closed = true;

        // Close all pipes
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        if (is_resource($this->resource)) {
            return proc_close($this->resource);
        }

        return -1;
    }

    /**
     * Get the full command that was executed.
     */
    public function getCommand(): string
    {
        $parts = [$this->executable];

        if ($this->workingDirectory !== '') {
            $parts[] = $this->workingDirectory . DIRECTORY_SEPARATOR . $this->script;
        } else {
            $parts[] = $this->script;
        }

        foreach ($this->arguments as $argument) {
            $parts[] = escapeshellarg($argument);
        }

        return implode(' ', $parts);
    }

    /**
     * Start the process.
     *
     * @param array<string, string> $environment
     * @throws ProcessStartException
     */
    private function start(array $environment): void
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $command = $this->getCommand();
        $cwd = $this->workingDirectory !== '' ? $this->workingDirectory : null;
        $env = !empty($environment) ? array_merge($_ENV, $environment) : null;

        // Suppress warning since we handle failure gracefully via exception
        $this->resource = @proc_open($command, $descriptorSpec, $this->pipes, $cwd, $env);

        if ($this->resource === false) {
            throw new ProcessStartException($this->script, 'proc_open failed');
        }

        $this->startTime = time();

        // Set stdout and stderr to non-blocking
        if (isset($this->pipes[1])) {
            stream_set_blocking($this->pipes[1], false);
        }
        if (isset($this->pipes[2])) {
            stream_set_blocking($this->pipes[2], false);
        }
    }

    /**
     * Ensure resources are cleaned up on destruction.
     */
    public function __destruct()
    {
        $this->close();
    }
}

