<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Exception;

/**
 * Exception thrown when a process exceeds its maximum execution time.
 */
class ProcessTimeoutException extends ProcessException
{
    public function __construct(
        public readonly string $script,
        public readonly int $maxExecutionTime,
        public readonly int $actualTime
    ) {
        parent::__construct(
            "Process '{$script}' exceeded max execution time of {$maxExecutionTime}s (ran for {$actualTime}s)"
        );
    }
}

