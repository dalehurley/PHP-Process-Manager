<?php

declare(strict_types=1);

/**
 * Example: Parallel Tool Calls with Claude
 *
 * This example demonstrates how to run multiple Claude tool-calling
 * workflows in parallel using PHP Process Manager.
 *
 * Use cases:
 *   - Extract structured data from multiple documents
 *   - Run analysis tasks concurrently
 *   - Multi-agent systems with parallel subtasks
 *
 * Prerequisites:
 *   composer require claude-php/claude-php-sdk
 *   export ANTHROPIC_API_KEY="your-api-key"
 *
 * Usage:
 *   php parallel-tools.php
 */

// Use local vendor if available (for Claude SDK), fallback to root
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use DaleHurley\ProcessManager\ProcessManager;
use DaleHurley\ProcessManager\Output\ConsoleOutputHandler;

// Define multiple tool-calling tasks to run in parallel
$tasks = [
    [
        'id' => 'weather_sf',
        'description' => 'Get weather for San Francisco',
        'prompt' => 'What is the current weather in San Francisco?',
        'tool' => 'get_weather',
    ],
    [
        'id' => 'weather_nyc',
        'description' => 'Get weather for New York',
        'prompt' => 'What is the current weather in New York City?',
        'tool' => 'get_weather',
    ],
    [
        'id' => 'weather_london',
        'description' => 'Get weather for London',
        'prompt' => 'What is the current weather in London?',
        'tool' => 'get_weather',
    ],
    [
        'id' => 'calculate_tip',
        'description' => 'Calculate restaurant tip',
        'prompt' => 'Calculate a 20% tip on a $85.50 bill',
        'tool' => 'calculate',
    ],
    [
        'id' => 'extract_contact',
        'description' => 'Extract contact information',
        'prompt' => 'Extract the contact info: John Smith, john@example.com, +1-555-0123',
        'tool' => 'extract_contact',
    ],
];

echo "=== Parallel Tool Calls with Claude ===\n\n";
echo "Running " . count($tasks) . " tool-calling tasks in parallel...\n\n";

// Create process manager - run up to 3 concurrent tool workflows
$manager = new ProcessManager(
    executable: 'php',
    workingDirectory: __DIR__,
    maxConcurrentProcesses: 3,
    sleepInterval: 1,
    outputHandler: new ConsoleOutputHandler(useColors: true)
);

// Queue each tool task
foreach ($tasks as $task) {
    $encodedData = base64_encode(json_encode($task));

    $manager->addScript(
        script: 'worker-tool.php',
        maxExecutionTime: 60,
        arguments: [$encodedData]
    );
}

// Execute all tasks in parallel
$startTime = microtime(true);
$results = $manager->run();
$totalTime = round(microtime(true) - $startTime, 2);

echo "\n=== Tool Results ===\n\n";

foreach ($results as $index => $result) {
    $task = $tasks[$index];

    echo "🔧 {$task['description']} ({$task['tool']}):\n";

    if ($result->wasSuccessful) {
        $output = json_decode(trim($result->output), true);

        if ($output && isset($output['tool_result'])) {
            echo "   Result: " . json_encode($output['tool_result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

            if (isset($output['claude_response'])) {
                echo "   Claude: " . $output['claude_response'] . "\n";
            }
        } else {
            echo "   " . trim($result->output) . "\n";
        }
    } else {
        echo "   ❌ Failed: " . trim($result->errorOutput) . "\n";
    }

    echo "\n";
}

echo "=== Summary ===\n";
echo "Total time: {$totalTime}s (parallel)\n";
echo "Estimated sequential: ~" . round($totalTime * count($tasks) / 3, 1) . "s\n";
echo "Successful: " . count(array_filter($results, fn($r) => $r->wasSuccessful)) . "/" . count($results) . "\n";

