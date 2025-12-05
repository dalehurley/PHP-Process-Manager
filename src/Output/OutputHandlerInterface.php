<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Output;

/**
 * Interface for handling process manager output.
 */
interface OutputHandlerInterface
{
    /**
     * Output a message when a script is added to the queue.
     */
    public function scriptAdded(string $script): void;

    /**
     * Output a message when a script completes successfully.
     */
    public function scriptCompleted(string $script): void;

    /**
     * Output a message when a script is killed due to timeout.
     */
    public function scriptKilled(string $script): void;

    /**
     * Output a general info message.
     */
    public function info(string $message): void;

    /**
     * Output an error message.
     */
    public function error(string $message): void;
}

