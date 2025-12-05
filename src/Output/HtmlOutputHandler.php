<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Output;

/**
 * HTML output handler for web environments.
 */
final class HtmlOutputHandler implements OutputHandlerInterface
{
    public function __construct(
        private readonly bool $flush = true
    ) {
    }

    public function scriptAdded(string $script): void
    {
        $this->output("<span style='color: orange;'>[QUEUED]</span> {$script}");
    }

    public function scriptCompleted(string $script): void
    {
        $this->output("<span style='color: green;'>[DONE]</span> {$script}");
    }

    public function scriptKilled(string $script): void
    {
        $this->output("<span style='color: red;'>[KILLED]</span> {$script}");
    }

    public function info(string $message): void
    {
        $this->output("<span style='color: cyan;'>[INFO]</span> {$message}");
    }

    public function error(string $message): void
    {
        $this->output("<span style='color: red;'>[ERROR]</span> {$message}");
    }

    private function output(string $message): void
    {
        echo $message . "<br />\n";

        if ($this->flush) {
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
    }
}

