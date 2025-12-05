<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests\Output;

use DaleHurley\ProcessManager\Output\HtmlOutputHandler;
use DaleHurley\ProcessManager\Output\OutputHandlerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HtmlOutputHandler::class)]
final class HtmlOutputHandlerTest extends TestCase
{
    #[Test]
    public function it_implements_output_handler_interface(): void
    {
        $handler = new HtmlOutputHandler(flush: false);
        $this->assertInstanceOf(OutputHandlerInterface::class, $handler);
    }

    #[Test]
    public function it_outputs_html_for_script_added(): void
    {
        $handler = new HtmlOutputHandler(flush: false);

        ob_start();
        $handler->scriptAdded('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('<span', $output);
        $this->assertStringContainsString('orange', $output);
        $this->assertStringContainsString('[QUEUED]', $output);
        $this->assertStringContainsString('test.php', $output);
        $this->assertStringContainsString('<br />', $output);
    }

    #[Test]
    public function it_outputs_html_for_script_completed(): void
    {
        $handler = new HtmlOutputHandler(flush: false);

        ob_start();
        $handler->scriptCompleted('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('green', $output);
        $this->assertStringContainsString('[DONE]', $output);
    }

    #[Test]
    public function it_outputs_html_for_script_killed(): void
    {
        $handler = new HtmlOutputHandler(flush: false);

        ob_start();
        $handler->scriptKilled('test.php');
        $output = ob_get_clean();

        $this->assertStringContainsString('red', $output);
        $this->assertStringContainsString('[KILLED]', $output);
    }

    #[Test]
    public function it_outputs_html_for_info(): void
    {
        $handler = new HtmlOutputHandler(flush: false);

        ob_start();
        $handler->info('Info message');
        $output = ob_get_clean();

        $this->assertStringContainsString('cyan', $output);
        $this->assertStringContainsString('[INFO]', $output);
        $this->assertStringContainsString('Info message', $output);
    }

    #[Test]
    public function it_outputs_html_for_error(): void
    {
        $handler = new HtmlOutputHandler(flush: false);

        ob_start();
        $handler->error('Error message');
        $output = ob_get_clean();

        $this->assertStringContainsString('red', $output);
        $this->assertStringContainsString('[ERROR]', $output);
        $this->assertStringContainsString('Error message', $output);
    }
}

