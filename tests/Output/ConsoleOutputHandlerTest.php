<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests\Output;

use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;
use DaleHurley\ProcessManager\Output\OutputHandlerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ConsoleOutputHandler::class)]
final class ConsoleOutputHandlerTest extends TestCase
{
    #[Test]
    public function it_implements_output_handler_interface(): void
    {
        $handler = new ConsoleOutputHandler();
        $this->assertInstanceOf(OutputHandlerInterface::class, $handler);
    }

    #[Test]
    public function it_outputs_script_added_message(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->scriptAdded('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('[QUEUED]', $output);
        $this->assertStringContainsString('test.php', $output);
    }

    #[Test]
    public function it_outputs_script_completed_message(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->scriptCompleted('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('[DONE]', $output);
        $this->assertStringContainsString('test.php', $output);
    }

    #[Test]
    public function it_outputs_script_killed_message(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->scriptKilled('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('[KILLED]', $output);
        $this->assertStringContainsString('test.php', $output);
    }

    #[Test]
    public function it_outputs_info_message(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->info('Test message');
        $output = ob_get_clean();

        $this->assertStringContainsString('[INFO]', $output);
        $this->assertStringContainsString('Test message', $output);
    }

    #[Test]
    public function it_outputs_error_message(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->error('Error message');
        $output = ob_get_clean();

        $this->assertStringContainsString('[ERROR]', $output);
        $this->assertStringContainsString('Error message', $output);
    }

    #[Test]
    public function it_outputs_with_colors_when_enabled(): void
    {
        $handler = new ConsoleOutputHandler(useColors: true);

        ob_start();
        $handler->scriptAdded('test.php');
        $output = ob_get_clean();

        // Check for ANSI color codes
        $this->assertStringContainsString("\033[", $output);
    }

    #[Test]
    public function it_outputs_without_colors_when_disabled(): void
    {
        $handler = new ConsoleOutputHandler(useColors: false);

        ob_start();
        $handler->scriptAdded('test.php');
        $output = ob_get_clean();

        // Should not contain ANSI color codes
        $this->assertStringNotContainsString("\033[", $output);
    }
}

