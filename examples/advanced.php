<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

/**
 * Advanced example showing fluent API and batch script addition.
 */

$manager = new ProcessManager();

// Configure using fluent API
$manager
    ->setExecutable('php')
    ->setWorkingDirectory(__DIR__)
    ->setMaxConcurrentProcesses(5)
    ->setSleepInterval(1)
    ->setOutputHandler(new ConsoleOutputHandler(useColors: true));

// Add multiple scripts at once
$manager->addScripts([
    'worker.php',
    ['script' => 'worker.php', 'maxExecutionTime' => 3],
    ['script' => 'worker.php', 'maxExecutionTime' => 10],
    ['script' => 'worker.php', 'maxExecutionTime' => 5],
]);

// Or add scripts with arguments and environment variables
$manager->addScript(
    script: 'worker.php',
    maxExecutionTime: 30,
    arguments: [],
    environment: ['DEBUG' => '1']
);

echo "Queue size: " . $manager->getQueueCount() . " scripts\n\n";

$results = $manager->run();

// Analyze results
$successful = array_filter($results, fn($r) => $r->wasSuccessful);
$failed = array_filter($results, fn($r) => !$r->wasSuccessful);

echo "\n=== Summary ===\n";
echo "Successful: " . count($successful) . "\n";
echo "Failed/Killed: " . count($failed) . "\n";
echo "Total time: " . array_sum(array_map(fn($r) => $r->elapsedTime, $results)) . "s (cumulative)\n";
