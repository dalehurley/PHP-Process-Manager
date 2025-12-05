<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests;

use DaleHurley\ProcessManager\Process;
use DaleHurley\ProcessManager\Exception\ProcessStartException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Process::class)]
final class ProcessTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
    }

    #[Test]
    public function it_creates_a_process(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'success.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10
        );

        $this->assertSame('php', $process->executable);
        $this->assertSame('success.php', $process->script);
        $this->assertSame($this->fixturesDir, $process->workingDirectory);
        $this->assertSame(10, $process->maxExecutionTime);
    }

    #[Test]
    public function it_runs_a_successful_process(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'success.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10
        );

        // Wait for process to complete
        while ($process->isRunning()) {
            usleep(50000); // 50ms
        }

        $this->assertFalse($process->isRunning());
        $this->assertSame(0, $process->getExitCode());
        $this->assertStringContainsString('Success output', $process->getOutput());
    }

    #[Test]
    public function it_captures_error_output(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'failure.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10
        );

        while ($process->isRunning()) {
            usleep(50000);
        }

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('Error output', $process->getErrorOutput());
    }

    #[Test]
    public function it_detects_timeout(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'sleep.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 1,
            arguments: ['5'] // Sleep for 5 seconds
        );

        // Process should not have timed out immediately
        $this->assertFalse($process->hasExceededTimeout());

        // Wait for timeout
        sleep(2);

        $this->assertTrue($process->hasExceededTimeout());
        $process->terminate();
        $process->close();
    }

    #[Test]
    public function it_returns_elapsed_time(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'sleep.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10,
            arguments: ['1']
        );

        $this->assertGreaterThanOrEqual(0, $process->getElapsedTime());

        while ($process->isRunning()) {
            usleep(100000);
        }

        $this->assertGreaterThanOrEqual(1, $process->getElapsedTime());
    }

    #[Test]
    public function it_returns_process_id(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'sleep.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10,
            arguments: ['1']
        );

        $pid = $process->getPid();
        $this->assertIsInt($pid);
        $this->assertGreaterThan(0, $pid);

        $process->terminate();
        $process->close();
    }

    #[Test]
    public function it_builds_correct_command(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'echo.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 10,
            arguments: ['arg1', 'arg2']
        );

        $command = $process->getCommand();
        $this->assertStringContainsString('php', $command);
        $this->assertStringContainsString('echo.php', $command);
        $this->assertStringContainsString("'arg1'", $command);
        $this->assertStringContainsString("'arg2'", $command);

        $process->close();
    }

    #[Test]
    public function it_can_be_terminated(): void
    {
        $process = new Process(
            executable: 'php',
            script: 'sleep.php',
            workingDirectory: $this->fixturesDir,
            maxExecutionTime: 60,
            arguments: ['30']
        );

        $this->assertTrue($process->isRunning());

        $result = $process->terminate();
        $this->assertTrue($result);

        // Give it a moment to terminate
        usleep(100000);

        $process->close();
    }

    #[Test]
    public function it_throws_exception_for_invalid_script(): void
    {
        $this->expectException(ProcessStartException::class);

        new Process(
            executable: 'nonexistent_command_xyz',
            script: 'nonexistent.php',
            workingDirectory: '/nonexistent/path',
            maxExecutionTime: 10
        );
    }
}

