<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager;

/**
 * Represents the result of a completed process.
 */
final readonly class ProcessResult
{
    public function __construct(
        public string $script,
        public int $exitCode,
        public string $output,
        public string $errorOutput,
        public int $elapsedTime,
        public bool $wasKilled,
        public bool $wasSuccessful
    ) {
    }

    /**
     * Check if the process completed without being killed.
     */
    public function completedNormally(): bool
    {
        return !$this->wasKilled;
    }

    /**
     * Check if the process had any error output.
     */
    public function hasErrors(): bool
    {
        return $this->errorOutput !== '';
    }

    /**
     * Convert the result to an array.
     *
     * @return array{script: string, exitCode: int, output: string, errorOutput: string, elapsedTime: int, wasKilled: bool, wasSuccessful: bool}
     */
    public function toArray(): array
    {
        return [
            'script' => $this->script,
            'exitCode' => $this->exitCode,
            'output' => $this->output,
            'errorOutput' => $this->errorOutput,
            'elapsedTime' => $this->elapsedTime,
            'wasKilled' => $this->wasKilled,
            'wasSuccessful' => $this->wasSuccessful,
        ];
    }
}

