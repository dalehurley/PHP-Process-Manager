<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Output;

/**
 * Console output handler for CLI environments.
 */
final class ConsoleOutputHandler implements OutputHandlerInterface
{
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_RESET = "\033[0m";

    public function __construct(
        private readonly bool $useColors = true
    ) {
    }

    public function scriptAdded(string $script): void
    {
        $this->writeLine(
            $this->colorize("[QUEUED]", self::COLOR_YELLOW) . " {$script}"
        );
    }

    public function scriptCompleted(string $script): void
    {
        $this->writeLine(
            $this->colorize("[DONE]", self::COLOR_GREEN) . " {$script}"
        );
    }

    public function scriptKilled(string $script): void
    {
        $this->writeLine(
            $this->colorize("[KILLED]", self::COLOR_RED) . " {$script}"
        );
    }

    public function info(string $message): void
    {
        $this->writeLine(
            $this->colorize("[INFO]", self::COLOR_CYAN) . " {$message}"
        );
    }

    public function error(string $message): void
    {
        $this->writeLine(
            $this->colorize("[ERROR]", self::COLOR_RED) . " {$message}"
        );
    }

    private function colorize(string $text, string $color): string
    {
        if (!$this->useColors) {
            return $text;
        }

        return $color . $text . self::COLOR_RESET;
    }

    private function writeLine(string $message): void
    {
        echo $message . PHP_EOL;
    }
}

