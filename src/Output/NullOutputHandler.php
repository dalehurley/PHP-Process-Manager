<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Output;

/**
 * A null output handler that discards all output.
 */
final class NullOutputHandler implements OutputHandlerInterface
{
    public function scriptAdded(string $script): void
    {
        // Intentionally empty
    }

    public function scriptCompleted(string $script): void
    {
        // Intentionally empty
    }

    public function scriptKilled(string $script): void
    {
        // Intentionally empty
    }

    public function info(string $message): void
    {
        // Intentionally empty
    }

    public function error(string $message): void
    {
        // Intentionally empty
    }
}

