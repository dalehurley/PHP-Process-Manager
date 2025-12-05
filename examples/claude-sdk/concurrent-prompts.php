<?php

declare(strict_types=1);

/**
 * Example: Concurrent Claude API Calls
 *
 * This example demonstrates how to use PHP Process Manager to make
 * multiple Claude API calls simultaneously, dramatically reducing
 * total processing time for batch operations.
 *
 * Prerequisites:
 *   composer require claude-php/claude-php-sdk
 *   export ANTHROPIC_API_KEY="your-api-key"
 *
 * Usage:
 *   php concurrent-prompts.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

// Define the prompts we want to process concurrently
$prompts = [
    [
        'id' => 'summary',
        'prompt' => 'Summarize the benefits of parallel processing in 2 sentences.',
    ],
    [
        'id' => 'haiku',
        'prompt' => 'Write a haiku about concurrent programming.',
    ],
    [
        'id' => 'explanation',
        'prompt' => 'Explain what a process pool is in one paragraph.',
    ],
];

echo "=== Concurrent Claude API Calls ===\n\n";
echo "Processing " . count($prompts) . " prompts in parallel...\n\n";

// Create a process manager with 3 concurrent processes
$manager = new ProcessManager(
    executable: 'php',
    workingDirectory: __DIR__,
    maxConcurrentProcesses: 3,
    sleepInterval: 1,
    outputHandler: new ConsoleOutputHandler(useColors: true)
);

// Add each prompt as a separate process
foreach ($prompts as $promptConfig) {
    // Pass prompt data as base64-encoded JSON to avoid shell escaping issues
    $encodedData = base64_encode(json_encode($promptConfig));

    $manager->addScript(
        script: 'worker-prompt.php',
        maxExecutionTime: 60,
        arguments: [$encodedData]
    );
}

// Run all prompts concurrently
$startTime = microtime(true);
$results = $manager->run();
$totalTime = round(microtime(true) - $startTime, 2);

echo "\n=== Results ===\n\n";

foreach ($results as $index => $result) {
    $promptId = $prompts[$index]['id'];

    echo "📝 {$promptId}:\n";

    if ($result->wasSuccessful) {
        // Parse the JSON output from the worker
        $output = json_decode(trim($result->output), true);

        if ($output && isset($output['response'])) {
            echo "   " . str_replace("\n", "\n   ", $output['response']) . "\n";
        } else {
            echo "   " . trim($result->output) . "\n";
        }
    } else {
        echo "   ❌ Failed: " . trim($result->errorOutput) . "\n";
    }

    echo "\n";
}

echo "=== Summary ===\n";
echo "Total time: {$totalTime}s\n";
echo "Successful: " . count(array_filter($results, fn($r) => $r->wasSuccessful)) . "/" . count($results) . "\n";
echo "\n";

// Compare to sequential time estimate
$avgTimePerCall = $totalTime / count($results);
$sequentialEstimate = $avgTimePerCall * count($results);
echo "💡 Sequential estimate: ~" . round($sequentialEstimate * 3, 1) . "s\n";
echo "⚡ Parallel actual: {$totalTime}s\n";

