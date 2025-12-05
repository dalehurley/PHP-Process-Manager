<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

// Create a process manager with console output
$manager = new ProcessManager(
    executable: 'php',
    workingDirectory: __DIR__,
    maxConcurrentProcesses: 3,
    sleepInterval: 1,
    outputHandler: new ConsoleOutputHandler()
);

// Add scripts to the queue with different max execution times
$manager->addScript('worker.php', maxExecutionTime: 5);
$manager->addScript('worker.php', maxExecutionTime: 5);
$manager->addScript('worker.php', maxExecutionTime: 2); // This one might timeout
$manager->addScript('worker.php', maxExecutionTime: 10);
$manager->addScript('worker.php', maxExecutionTime: 10);

// Run all scripts and get results
$results = $manager->run();

echo "\n=== Results ===\n";
foreach ($results as $index => $result) {
    echo sprintf(
        "Script #%d (%s): %s in %ds (exit: %d)\n",
        $index + 1,
        $result->script,
        $result->wasSuccessful ? 'SUCCESS' : ($result->wasKilled ? 'KILLED' : 'FAILED'),
        $result->elapsedTime,
        $result->exitCode
    );
}
