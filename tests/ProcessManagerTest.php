<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests;

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\ProcessResult;
use DaleHurley\ProcessManager\Output\NullOutputHandler;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProcessManager::class)]
final class ProcessManagerTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = __DIR__ . '/fixtures';
    }

    #[Test]
    public function it_creates_with_default_values(): void
    {
        $manager = new ProcessManager();

        $this->assertSame(0, $manager->getQueueCount());
        $this->assertSame(0, $manager->getRunningCount());
    }

    #[Test]
    public function it_creates_with_custom_values(): void
    {
        $manager = new ProcessManager(
            executable: 'python',
            workingDirectory: '/tmp',
            maxConcurrentProcesses: 10,
            sleepInterval: 2
        );

        $this->assertSame(0, $manager->getQueueCount());
    }

    #[Test]
    public function it_adds_scripts_to_queue(): void
    {
        $manager = new ProcessManager();

        $manager->addScript('script1.php');
        $this->assertSame(1, $manager->getQueueCount());

        $manager->addScript('script2.php', maxExecutionTime: 60);
        $this->assertSame(2, $manager->getQueueCount());

        $manager->addScript('script3.php', maxExecutionTime: 120, arguments: ['--verbose']);
        $this->assertSame(3, $manager->getQueueCount());
    }

    #[Test]
    public function it_adds_multiple_scripts_at_once(): void
    {
        $manager = new ProcessManager();

        $manager->addScripts([
            'script1.php',
            'script2.php',
            ['script' => 'script3.php', 'maxExecutionTime' => 60],
        ]);

        $this->assertSame(3, $manager->getQueueCount());
    }

    #[Test]
    public function it_clears_queue(): void
    {
        $manager = new ProcessManager();

        $manager->addScript('script1.php');
        $manager->addScript('script2.php');
        $this->assertSame(2, $manager->getQueueCount());

        $manager->clearQueue();
        $this->assertSame(0, $manager->getQueueCount());
    }

    #[Test]
    public function it_uses_fluent_api(): void
    {
        $manager = new ProcessManager();

        $result = $manager
            ->setExecutable('php')
            ->setWorkingDirectory($this->fixturesDir)
            ->setMaxConcurrentProcesses(5)
            ->setSleepInterval(1)
            ->setOutputHandler(new NullOutputHandler())
            ->addScript('success.php');

        $this->assertInstanceOf(ProcessManager::class, $result);
        $this->assertSame(1, $manager->getQueueCount());
    }

    #[Test]
    public function it_runs_successful_scripts(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 2,
            sleepInterval: 1
        );

        $manager->addScript('success.php', maxExecutionTime: 10);
        $manager->addScript('success.php', maxExecutionTime: 10);

        $results = $manager->run();

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(ProcessResult::class, $result);
            $this->assertTrue($result->wasSuccessful);
            $this->assertFalse($result->wasKilled);
            $this->assertSame(0, $result->exitCode);
        }
    }

    #[Test]
    public function it_handles_failed_scripts(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 2,
            sleepInterval: 1
        );

        $manager->addScript('failure.php', maxExecutionTime: 10);

        $results = $manager->run();

        $this->assertCount(1, $results);
        $this->assertFalse($results[0]->wasSuccessful);
        $this->assertSame(1, $results[0]->exitCode);
        $this->assertTrue($results[0]->hasErrors());
    }

    #[Test]
    public function it_kills_timed_out_scripts(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 1,
            sleepInterval: 1
        );

        // Script sleeps for 5 seconds but timeout is 2 seconds
        $manager->addScript('sleep.php', maxExecutionTime: 2, arguments: ['5']);

        $results = $manager->run();

        $this->assertCount(1, $results);
        $this->assertTrue($results[0]->wasKilled);
        $this->assertFalse($results[0]->wasSuccessful);
    }

    #[Test]
    public function it_respects_max_concurrent_processes(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 2,
            sleepInterval: 1
        );

        // Add 4 scripts that each sleep for 1 second
        for ($i = 0; $i < 4; $i++) {
            $manager->addScript('sleep.php', maxExecutionTime: 10, arguments: ['1']);
        }

        $startTime = time();
        $results = $manager->run();
        $totalTime = time() - $startTime;

        $this->assertCount(4, $results);

        // With 2 concurrent processes and 4 scripts sleeping 1 second each,
        // minimum time is ~2 seconds (2 batches)
        $this->assertGreaterThanOrEqual(2, $totalTime);

        // All should be successful
        foreach ($results as $result) {
            $this->assertTrue($result->wasSuccessful);
        }
    }

    #[Test]
    public function it_returns_empty_array_for_empty_queue(): void
    {
        $manager = new ProcessManager();

        $results = $manager->run();

        $this->assertSame([], $results);
    }

    #[Test]
    public function it_captures_script_output(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 1,
            sleepInterval: 1
        );

        $manager->addScript('echo.php', maxExecutionTime: 10, arguments: ['hello', 'world']);

        $results = $manager->run();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('hello world', $results[0]->output);
    }

    #[Test]
    public function it_tracks_elapsed_time(): void
    {
        $manager = new ProcessManager(
            executable: 'php',
            workingDirectory: $this->fixturesDir,
            maxConcurrentProcesses: 1,
            sleepInterval: 1
        );

        $manager->addScript('sleep.php', maxExecutionTime: 10, arguments: ['2']);

        $results = $manager->run();

        $this->assertCount(1, $results);
        $this->assertGreaterThanOrEqual(2, $results[0]->elapsedTime);
    }
}

