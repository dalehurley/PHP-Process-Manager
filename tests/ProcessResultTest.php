<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager\Tests;

use DaleHurley\ProcessManager\ProcessResult;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProcessResult::class)]
final class ProcessResultTest extends TestCase
{
    #[Test]
    public function it_stores_all_properties(): void
    {
        $result = new ProcessResult(
            script: 'test.php',
            exitCode: 0,
            output: 'stdout content',
            errorOutput: 'stderr content',
            elapsedTime: 5,
            wasKilled: false,
            wasSuccessful: true
        );

        $this->assertSame('test.php', $result->script);
        $this->assertSame(0, $result->exitCode);
        $this->assertSame('stdout content', $result->output);
        $this->assertSame('stderr content', $result->errorOutput);
        $this->assertSame(5, $result->elapsedTime);
        $this->assertFalse($result->wasKilled);
        $this->assertTrue($result->wasSuccessful);
    }

    #[Test]
    public function it_detects_completed_normally(): void
    {
        $normalResult = new ProcessResult(
            script: 'test.php',
            exitCode: 0,
            output: '',
            errorOutput: '',
            elapsedTime: 1,
            wasKilled: false,
            wasSuccessful: true
        );

        $killedResult = new ProcessResult(
            script: 'test.php',
            exitCode: -1,
            output: '',
            errorOutput: '',
            elapsedTime: 30,
            wasKilled: true,
            wasSuccessful: false
        );

        $this->assertTrue($normalResult->completedNormally());
        $this->assertFalse($killedResult->completedNormally());
    }

    #[Test]
    public function it_detects_errors(): void
    {
        $withErrors = new ProcessResult(
            script: 'test.php',
            exitCode: 1,
            output: '',
            errorOutput: 'Something went wrong',
            elapsedTime: 1,
            wasKilled: false,
            wasSuccessful: false
        );

        $withoutErrors = new ProcessResult(
            script: 'test.php',
            exitCode: 0,
            output: 'Success',
            errorOutput: '',
            elapsedTime: 1,
            wasKilled: false,
            wasSuccessful: true
        );

        $this->assertTrue($withErrors->hasErrors());
        $this->assertFalse($withoutErrors->hasErrors());
    }

    #[Test]
    public function it_converts_to_array(): void
    {
        $result = new ProcessResult(
            script: 'test.php',
            exitCode: 0,
            output: 'output',
            errorOutput: 'error',
            elapsedTime: 10,
            wasKilled: false,
            wasSuccessful: true
        );

        $array = $result->toArray();

        $this->assertSame([
            'script' => 'test.php',
            'exitCode' => 0,
            'output' => 'output',
            'errorOutput' => 'error',
            'elapsedTime' => 10,
            'wasKilled' => false,
            'wasSuccessful' => true,
        ], $array);
    }
}

