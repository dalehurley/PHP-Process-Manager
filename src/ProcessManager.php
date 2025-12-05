<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager;

use DaleHurley\ProcessManager\Exception\ProcessStartException;
use DaleHurley\ProcessManager\Output\NullOutputHandler;
use DaleHurley\ProcessManager\Output\OutputHandlerInterface;

/**
 * A multi-process manager for running concurrent background tasks.
 *
 * This class allows you to queue multiple scripts/commands and execute them
 * concurrently with configurable limits on parallelism and execution time.
 *
 * @example
 * ```php
 * $manager = new ProcessManager(
 *     executable: 'php',
 *     maxConcurrentProcesses: 3,
 *     sleepInterval: 1
 * );
 *
 * $manager->addScript('worker.php', maxExecutionTime: 60);
 * $manager->addScript('task.php', maxExecutionTime: 30);
 *
 * $results = $manager->run();
 * ```
 */
final class ProcessManager
{
    /** @var array<int, array{script: string, maxExecutionTime: int, arguments: array<string>, environment: array<string, string>}> */
    private array $queue = [];

    /** @var array<int, Process> */
    private array $running = [];

    /** @var array<int, ProcessResult> */
    private array $results = [];

    private OutputHandlerInterface $outputHandler;

    /**
     * Create a new ProcessManager instance.
     *
     * @param string $executable The command to execute (e.g., 'php', 'python')
     * @param string $workingDirectory The directory to run scripts from
     * @param int $maxConcurrentProcesses Maximum number of processes to run simultaneously
     * @param int $sleepInterval Seconds to wait between checking process status
     * @param OutputHandlerInterface|null $outputHandler Handler for output messages
     */
    public function __construct(
        private string $executable = 'php',
        private string $workingDirectory = '',
        private int $maxConcurrentProcesses = 3,
        private int $sleepInterval = 1,
        ?OutputHandlerInterface $outputHandler = null
    ) {
        $this->outputHandler = $outputHandler ?? new NullOutputHandler();
    }

    /**
     * Set the executable command.
     */
    public function setExecutable(string $executable): self
    {
        $this->executable = $executable;
        return $this;
    }

    /**
     * Set the working directory for scripts.
     */
    public function setWorkingDirectory(string $workingDirectory): self
    {
        $this->workingDirectory = $workingDirectory;
        return $this;
    }

    /**
     * Set the maximum number of concurrent processes.
     */
    public function setMaxConcurrentProcesses(int $count): self
    {
        $this->maxConcurrentProcesses = max(1, $count);
        return $this;
    }

    /**
     * Set the sleep interval between status checks.
     */
    public function setSleepInterval(int $seconds): self
    {
        $this->sleepInterval = max(0, $seconds);
        return $this;
    }

    /**
     * Set the output handler.
     */
    public function setOutputHandler(OutputHandlerInterface $handler): self
    {
        $this->outputHandler = $handler;
        return $this;
    }

    /**
     * Add a script to the execution queue.
     *
     * @param string $script The script filename or path
     * @param int $maxExecutionTime Maximum time in seconds before the process is killed
     * @param array<string> $arguments Additional command-line arguments
     * @param array<string, string> $environment Environment variables for the process
     */
    public function addScript(
        string $script,
        int $maxExecutionTime = 300,
        array $arguments = [],
        array $environment = []
    ): self {
        $this->queue[] = [
            'script' => $script,
            'maxExecutionTime' => $maxExecutionTime,
            'arguments' => $arguments,
            'environment' => $environment,
        ];
        return $this;
    }

    /**
     * Add multiple scripts to the execution queue.
     *
     * @param array<string|array{script: string, maxExecutionTime?: int, arguments?: array<string>, environment?: array<string, string>}> $scripts
     */
    public function addScripts(array $scripts): self
    {
        foreach ($scripts as $script) {
            if (is_string($script)) {
                $this->addScript($script);
            } else {
                $this->addScript(
                    $script['script'],
                    $script['maxExecutionTime'] ?? 300,
                    $script['arguments'] ?? [],
                    $script['environment'] ?? []
                );
            }
        }
        return $this;
    }

    /**
     * Get the number of scripts in the queue.
     */
    public function getQueueCount(): int
    {
        return count($this->queue);
    }

    /**
     * Get the number of currently running processes.
     */
    public function getRunningCount(): int
    {
        return count($this->running);
    }

    /**
     * Clear the execution queue.
     */
    public function clearQueue(): self
    {
        $this->queue = [];
        return $this;
    }

    /**
     * Run all queued scripts and return results.
     *
     * @return array<int, ProcessResult> Results for each completed process
     */
    public function run(): array
    {
        $this->results = [];
        $queueIndex = 0;
        $totalScripts = count($this->queue);

        while (true) {
            // Start new processes up to the limit
            while (count($this->running) < $this->maxConcurrentProcesses && $queueIndex < $totalScripts) {
                $task = $this->queue[$queueIndex];

                try {
                    $process = new Process(
                        executable: $this->executable,
                        script: $task['script'],
                        workingDirectory: $this->workingDirectory,
                        maxExecutionTime: $task['maxExecutionTime'],
                        arguments: $task['arguments'],
                        environment: $task['environment']
                    );

                    $this->running[$queueIndex] = $process;
                    $this->outputHandler->scriptAdded($task['script']);
                } catch (ProcessStartException $e) {
                    $this->outputHandler->error($e->getMessage());
                    $this->results[$queueIndex] = new ProcessResult(
                        script: $task['script'],
                        exitCode: -1,
                        output: '',
                        errorOutput: $e->getMessage(),
                        elapsedTime: 0,
                        wasKilled: false,
                        wasSuccessful: false
                    );
                }

                $queueIndex++;
            }

            // Check if we're done
            if (count($this->running) === 0 && $queueIndex >= $totalScripts) {
                break;
            }

            // Wait before checking status
            if ($this->sleepInterval > 0) {
                sleep($this->sleepInterval);
            }

            // Check running processes
            $this->checkRunningProcesses();
        }

        return $this->results;
    }

    /**
     * Check all running processes and handle completed/timed-out ones.
     */
    private function checkRunningProcesses(): void
    {
        foreach ($this->running as $index => $process) {
            $isRunning = $process->isRunning();
            $hasTimedOut = $process->hasExceededTimeout();

            if (!$isRunning || $hasTimedOut) {
                if ($hasTimedOut && $isRunning) {
                    // Kill the process
                    $process->terminate();
                    $this->outputHandler->scriptKilled($process->script);

                    $this->results[$index] = new ProcessResult(
                        script: $process->script,
                        exitCode: -1,
                        output: $process->getOutput(),
                        errorOutput: $process->getErrorOutput(),
                        elapsedTime: $process->getElapsedTime(),
                        wasKilled: true,
                        wasSuccessful: false
                    );
                } else {
                    // Process completed normally
                    $this->outputHandler->scriptCompleted($process->script);

                    $exitCode = $process->getExitCode() ?? -1;
                    $this->results[$index] = new ProcessResult(
                        script: $process->script,
                        exitCode: $exitCode,
                        output: $process->getOutput(),
                        errorOutput: $process->getErrorOutput(),
                        elapsedTime: $process->getElapsedTime(),
                        wasKilled: false,
                        wasSuccessful: $exitCode === 0
                    );
                }

                $process->close();
                unset($this->running[$index]);
            }
        }
    }
}

