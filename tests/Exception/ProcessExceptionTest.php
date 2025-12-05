<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests\Exception;

use DaleHurley\ProcessManager\Exception\ProcessException;
use DaleHurley\ProcessManager\Exception\ProcessStartException;
use DaleHurley\ProcessManager\Exception\ProcessTimeoutException;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProcessException::class)]
#[CoversClass(ProcessStartException::class)]
#[CoversClass(ProcessTimeoutException::class)]
final class ProcessExceptionTest extends TestCase
{
    #[Test]
    public function process_exception_extends_exception(): void
    {
        $exception = new ProcessException('Test error');

        $this->assertInstanceOf(Exception::class, $exception);
        $this->assertSame('Test error', $exception->getMessage());
    }

    #[Test]
    public function process_start_exception_formats_message(): void
    {
        $exception = new ProcessStartException('script.php');

        $this->assertInstanceOf(ProcessException::class, $exception);
        $this->assertStringContainsString('script.php', $exception->getMessage());
        $this->assertStringContainsString('Failed to start', $exception->getMessage());
    }

    #[Test]
    public function process_start_exception_includes_reason(): void
    {
        $exception = new ProcessStartException('script.php', 'File not found');

        $this->assertStringContainsString('script.php', $exception->getMessage());
        $this->assertStringContainsString('File not found', $exception->getMessage());
    }

    #[Test]
    public function process_timeout_exception_formats_message(): void
    {
        $exception = new ProcessTimeoutException(
            script: 'long-task.php',
            maxExecutionTime: 30,
            actualTime: 45
        );

        $this->assertInstanceOf(ProcessException::class, $exception);
        $this->assertSame('long-task.php', $exception->script);
        $this->assertSame(30, $exception->maxExecutionTime);
        $this->assertSame(45, $exception->actualTime);
        $this->assertStringContainsString('long-task.php', $exception->getMessage());
        $this->assertStringContainsString('30', $exception->getMessage());
        $this->assertStringContainsString('45', $exception->getMessage());
    }
}

