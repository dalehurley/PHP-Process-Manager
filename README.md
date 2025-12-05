# PHP Process Manager

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A lightweight **parallel process runner** for PHP. Execute multiple scripts or commands concurrently with configurable parallelism, timeouts, and real-time output tracking.

```
Sequential execution:        Parallel execution (3 concurrent):
┌────┐┌────┐┌────┐┌────┐┌────┐┌────┐    ┌────┐┌────┐
│ T1 ││ T2 ││ T3 ││ T4 ││ T5 ││ T6 │    │ T1 ││ T4 │
│ 5s ││ 5s ││ 5s ││ 5s ││ 5s ││ 5s │    │ 5s ││ 5s │
└────┘└────┘└────┘└────┘└────┘└────┘    ├────┤├────┤
         Total: 30 seconds              │ T2 ││ T5 │
                                        │ 5s ││ 5s │
                                        ├────┤├────┤
                                        │ T3 ││ T6 │
                                        │ 5s ││ 5s │
                                        └────┘└────┘
                                        Total: 10 seconds
```

---

## Overview

### What is it?

PHP Process Manager is a **concurrent task runner** that spawns and manages multiple OS processes simultaneously. Instead of running tasks one after another (sequential), it executes them in parallel—dramatically reducing total execution time for batch operations.

Think of it as a simple **process pool** or **worker spawner**: you queue up scripts, set a concurrency limit, and the manager handles execution, monitoring, timeouts, and result collection.

### Who is it for?

| Audience                  | Use Case                                                      |
| ------------------------- | ------------------------------------------------------------- |
| **Backend developers**    | Batch processing, data imports/exports, scheduled jobs        |
| **DevOps engineers**      | Deployment scripts, server maintenance, multi-host operations |
| **Data engineers**        | ETL pipelines, file processing, API data collection           |
| **QA engineers**          | Parallel test execution, load testing preparation             |
| **System administrators** | Bulk operations, log processing, backup scripts               |

### Why use it?

PHP is single-threaded by default. When you have independent tasks, running them sequentially wastes time:

| Scenario                   | Sequential | Parallel (5 workers) | Speedup |
| -------------------------- | ---------- | -------------------- | ------- |
| 10 API calls × 2s each     | 20s        | ~4s                  | **5×**  |
| 100 file imports × 1s each | 100s       | ~20s                 | **5×**  |
| 50 email sends × 0.5s each | 25s        | ~5s                  | **5×**  |

**This package is ideal when you need to:**

- Run the same script multiple times with different inputs
- Execute multiple independent scripts as part of a workflow
- Process batches of work faster by parallelising
- Add timeout protection to unreliable external calls
- Limit concurrency to avoid overwhelming external services

### When to use it (and when not to)

✅ **Good fit:**

- Tasks are **independent** and don't share state
- Each task can run as a **separate PHP script or CLI command**
- You need **timeout protection** for unreliable tasks
- You want to **limit concurrency** (e.g., max 5 API calls at once)
- Tasks take **seconds to minutes** to complete

❌ **Consider alternatives for:**

- Tasks requiring **shared memory** or real-time inter-process communication → use [parallel](https://www.php.net/manual/en/book.parallel.php) extension
- **Web request handling** with high throughput → use a message queue (Redis, RabbitMQ, SQS)
- **Sub-second task spawning** at high frequency → process overhead becomes significant
- **Long-running daemon processes** → use Supervisor or systemd instead

---

## Real-World Use Cases

### 1. Batch Data Import

Import thousands of records by processing files in parallel:

```php
$manager = new ProcessManager(executable: 'php', maxConcurrentProcesses: 5);

foreach (glob('/data/imports/*.csv') as $file) {
    $manager->addScript('import-worker.php', arguments: [$file], maxExecutionTime: 300);
}

$results = $manager->run();
echo "Imported " . count(array_filter($results, fn($r) => $r->wasSuccessful)) . " files\n";
```

### 2. Multi-API Data Collection

Fetch data from multiple APIs simultaneously:

```php
$endpoints = ['users', 'orders', 'products', 'inventory', 'analytics'];

$manager = new ProcessManager(executable: 'php', maxConcurrentProcesses: 3);

foreach ($endpoints as $endpoint) {
    $manager->addScript('fetch-api.php', arguments: [$endpoint], maxExecutionTime: 60);
}

$results = $manager->run(); // All 5 endpoints fetched in ~2 batches instead of 5 sequential calls
```

### 3. Image/Video Processing Pipeline

Process media files in parallel using CLI tools:

```php
$manager = new ProcessManager(executable: 'ffmpeg', maxConcurrentProcesses: 4);

foreach ($videoFiles as $video) {
    $manager->addScript("-i {$video} -vf scale=1280:720 output/{$video}", maxExecutionTime: 600);
}

$manager->run();
```

### 4. Database Migration Runner

Run independent migrations concurrently:

```php
$manager = new ProcessManager(executable: 'php', maxConcurrentProcesses: 3);

$manager->addScripts([
    ['script' => 'migrate-users.php', 'maxExecutionTime' => 300],
    ['script' => 'migrate-orders.php', 'maxExecutionTime' => 600],
    ['script' => 'migrate-products.php', 'maxExecutionTime' => 300],
    ['script' => 'migrate-analytics.php', 'maxExecutionTime' => 900],
]);

$results = $manager->run();
```

### 5. Parallel Test Execution

Run test suites faster:

```php
$manager = new ProcessManager(executable: 'php', maxConcurrentProcesses: 4);

foreach (glob('tests/*Test.php') as $testFile) {
    $manager->addScript('vendor/bin/phpunit', arguments: [$testFile], maxExecutionTime: 120);
}

$results = $manager->run();
$failed = array_filter($results, fn($r) => !$r->wasSuccessful);

exit(count($failed) > 0 ? 1 : 0);
```

### 6. Multi-Server Deployment

Deploy to multiple servers simultaneously:

```php
$servers = ['web1.example.com', 'web2.example.com', 'web3.example.com'];

$manager = new ProcessManager(executable: 'ssh', maxConcurrentProcesses: 10);

foreach ($servers as $server) {
    $manager->addScript("{$server} 'cd /app && git pull && composer install'", maxExecutionTime: 120);
}

$results = $manager->run();
```

---

## Features

- 🚀 **Concurrent Execution** - Run multiple processes in parallel
- ⏱️ **Timeout Management** - Automatically kill processes that exceed time limits
- 📊 **Result Tracking** - Get detailed results for each process (exit codes, output, timing)
- 🎨 **Flexible Output** - Console, HTML, or custom output handlers
- 🔧 **Fluent API** - Chain configuration methods for clean setup
- 🏷️ **Fully Typed** - PHP 8.2+ with strict typing and readonly classes

## Requirements

- PHP 8.2 or higher
- `proc_open` function enabled

## Installation

### Via Composer

```bash
composer require dalehurley/process-manager
```

### Manual Installation

Clone the repository and include the autoloader:

```bash
git clone https://github.com/dalehurley/PHP-Process-Manager.git
cd PHP-Process-Manager
composer install
```

## Quick Start

```php
<?php

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

$manager = new ProcessManager(
    executable: 'php',
    workingDirectory: '/path/to/scripts',
    maxConcurrentProcesses: 3,
    sleepInterval: 1,
    outputHandler: new ConsoleOutputHandler()
);

// Add scripts to the queue
$manager->addScript('task1.php', maxExecutionTime: 60);
$manager->addScript('task2.php', maxExecutionTime: 30);
$manager->addScript('task3.php', maxExecutionTime: 120);

// Execute and get results
$results = $manager->run();

foreach ($results as $result) {
    echo "{$result->script}: " . ($result->wasSuccessful ? 'SUCCESS' : 'FAILED') . "\n";
}
```

## Usage

### Basic Configuration

```php
use DaleHurley\ProcessManager\ProcessManager;

// Constructor parameters
$manager = new ProcessManager(
    executable: 'php',              // Command to execute
    workingDirectory: './scripts',  // Directory containing scripts
    maxConcurrentProcesses: 5,      // Max parallel processes
    sleepInterval: 1                // Seconds between status checks
);
```

### Fluent API

```php
$manager = new ProcessManager();

$manager
    ->setExecutable('python')
    ->setWorkingDirectory('/path/to/scripts')
    ->setMaxConcurrentProcesses(10)
    ->setSleepInterval(2)
    ->setOutputHandler(new ConsoleOutputHandler());
```

### Adding Scripts

```php
// Add a single script with default timeout (300 seconds)
$manager->addScript('worker.php');

// Add with custom timeout
$manager->addScript('long-task.php', maxExecutionTime: 600);

// Add with arguments and environment variables
$manager->addScript(
    script: 'process-data.php',
    maxExecutionTime: 120,
    arguments: ['--batch', '100'],
    environment: ['DEBUG' => '1', 'LOG_LEVEL' => 'verbose']
);

// Add multiple scripts at once
$manager->addScripts([
    'task1.php',
    'task2.php',
    ['script' => 'task3.php', 'maxExecutionTime' => 60],
]);
```

### Output Handlers

The package includes several output handlers:

```php
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;
use DaleHurley\ProcessManager\Output\HtmlOutputHandler;
use DaleHurley\ProcessManager\Output\NullOutputHandler;

// Console output with colors (for CLI)
$manager->setOutputHandler(new ConsoleOutputHandler(useColors: true));

// HTML output (for web)
$manager->setOutputHandler(new HtmlOutputHandler(flush: true));

// No output (silent mode - default)
$manager->setOutputHandler(new NullOutputHandler());
```

### Custom Output Handler

Implement the `OutputHandlerInterface` for custom output:

```php
use DaleHurley\ProcessManager\Output\OutputHandlerInterface;

class LogOutputHandler implements OutputHandlerInterface
{
    public function __construct(private Logger $logger) {}

    public function scriptAdded(string $script): void
    {
        $this->logger->info("Queued: {$script}");
    }

    public function scriptCompleted(string $script): void
    {
        $this->logger->info("Completed: {$script}");
    }

    public function scriptKilled(string $script): void
    {
        $this->logger->warning("Killed: {$script}");
    }

    public function info(string $message): void
    {
        $this->logger->info($message);
    }

    public function error(string $message): void
    {
        $this->logger->error($message);
    }
}
```

### Working with Results

```php
$results = $manager->run();

foreach ($results as $result) {
    // Access result properties
    echo "Script: {$result->script}\n";
    echo "Exit Code: {$result->exitCode}\n";
    echo "Duration: {$result->elapsedTime}s\n";
    echo "Success: " . ($result->wasSuccessful ? 'Yes' : 'No') . "\n";
    echo "Killed: " . ($result->wasKilled ? 'Yes' : 'No') . "\n";

    if ($result->output) {
        echo "Output: {$result->output}\n";
    }

    if ($result->hasErrors()) {
        echo "Errors: {$result->errorOutput}\n";
    }

    // Convert to array
    $data = $result->toArray();
}

// Analyze results
$successful = array_filter($results, fn($r) => $r->wasSuccessful);
$failed = array_filter($results, fn($r) => !$r->wasSuccessful);
$killed = array_filter($results, fn($r) => $r->wasKilled);
```

## API Reference

### ProcessManager

| Method                                              | Description                        |
| --------------------------------------------------- | ---------------------------------- |
| `setExecutable(string $executable)`                 | Set the command to execute         |
| `setWorkingDirectory(string $path)`                 | Set the working directory          |
| `setMaxConcurrentProcesses(int $count)`             | Set max parallel processes         |
| `setSleepInterval(int $seconds)`                    | Set interval between status checks |
| `setOutputHandler(OutputHandlerInterface $handler)` | Set the output handler             |
| `addScript(string $script, ...)`                    | Add a script to the queue          |
| `addScripts(array $scripts)`                        | Add multiple scripts               |
| `getQueueCount()`                                   | Get number of queued scripts       |
| `getRunningCount()`                                 | Get number of running processes    |
| `clearQueue()`                                      | Clear the script queue             |
| `run()`                                             | Execute all queued scripts         |

### ProcessResult

| Property        | Type     | Description                |
| --------------- | -------- | -------------------------- |
| `script`        | `string` | Script name                |
| `exitCode`      | `int`    | Process exit code          |
| `output`        | `string` | stdout content             |
| `errorOutput`   | `string` | stderr content             |
| `elapsedTime`   | `int`    | Execution time in seconds  |
| `wasKilled`     | `bool`   | Whether process was killed |
| `wasSuccessful` | `bool`   | Whether process succeeded  |

## Upgrading from v1.x

The 2.0 release is a complete rewrite with breaking changes:

```php
// Old (v1.x)
$manager = new Processmanager();
$manager->executable = "php";
$manager->root = "";
$manager->processes = 3;
$manager->show_output = true;
$manager->addScript("script.php", 300);
$manager->exec();

// New (v2.x)
use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

$manager = new ProcessManager(
    executable: 'php',
    workingDirectory: '',
    maxConcurrentProcesses: 3,
    outputHandler: new ConsoleOutputHandler()
);
$manager->addScript('script.php', maxExecutionTime: 300);
$results = $manager->run();
```

### Key Changes

- Namespace: `DaleHurley\ProcessManager`
- Class renamed: `Processmanager` → `ProcessManager`
- Method renamed: `exec()` → `run()`
- Property renamed: `root` → `workingDirectory`
- Property renamed: `processes` → `maxConcurrentProcesses`
- Output handling now uses dedicated handler classes
- Returns detailed `ProcessResult` objects instead of void

## Testing

Run the test suite with PHPUnit:

```bash
composer test
```

Run static analysis with PHPStan (level 8):

```bash
composer analyse
```

---

## Alternatives

This package is intentionally simple and lightweight. Depending on your needs, consider these alternatives:

### For More Complex Process Management

| Package                                                                    | Description                             | Best For                                  |
| -------------------------------------------------------------------------- | --------------------------------------- | ----------------------------------------- |
| [symfony/process](https://symfony.com/doc/current/components/process.html) | Full-featured process component         | Single process with advanced I/O handling |
| [spatie/async](https://github.com/spatie/async)                            | Asynchronous process handling with Pool | Similar use case with event-driven API    |
| [amphp/parallel](https://amphp.org/parallel)                               | True parallel execution with workers    | High-performance async applications       |

### For Queue-Based Processing

| Package                                                                                              | Description                          | Best For                                  |
| ---------------------------------------------------------------------------------------------------- | ------------------------------------ | ----------------------------------------- |
| [Laravel Queues](https://laravel.com/docs/queues)                                                    | Queue system with multiple backends  | Laravel applications, distributed workers |
| [Symfony Messenger](https://symfony.com/doc/current/messenger.html)                                  | Message bus with queue transport     | Symfony applications, event-driven        |
| [php-enqueue](https://php-enqueue.github.io/)                                                        | Framework-agnostic queue abstraction | Multi-backend queue support               |
| [Beanstalkd](https://beanstalkd.github.io/) + [Pheanstalk](https://github.com/pheanstalk/pheanstalk) | Lightweight job queue                | Simple job queuing                        |

### For True Multi-Threading

| Package                                                     | Description                          | Best For                                         |
| ----------------------------------------------------------- | ------------------------------------ | ------------------------------------------------ |
| [parallel](https://www.php.net/manual/en/book.parallel.php) | PHP extension for parallel execution | Shared memory, true threading (requires ZTS PHP) |
| [pthreads](https://github.com/krakjoe/pthreads)             | Threading extension (PHP 7 only)     | Legacy threading needs                           |

### When to Choose This Package

Choose **PHP Process Manager** when you need:

- ✅ Simple, zero-dependency process spawning
- ✅ Quick setup without infrastructure (no Redis, no queue server)
- ✅ Timeout management built-in
- ✅ Result collection from all processes
- ✅ CLI script orchestration
- ✅ Lightweight alternative to full queue systems

Choose **alternatives** when you need:

- ❌ Persistent job storage and retry logic → use queues
- ❌ Distributed processing across servers → use message queues
- ❌ Shared memory between tasks → use parallel extension
- ❌ Web-scale throughput → use dedicated worker systems

---

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

- Original concept by Matou Havlena (havlena.net)
- Modernized by Dale Hurley (dalehurley.com)
