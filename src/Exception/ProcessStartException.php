<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Exception;

/**
 * Exception thrown when a process fails to start.
 */
class ProcessStartException extends ProcessException
{
    public function __construct(string $script, string $reason = '')
    {
        $message = "Failed to start process for script: {$script}";
        if ($reason !== '') {
            $message .= " - {$reason}";
        }
        parent::__construct($message);
    }
}

