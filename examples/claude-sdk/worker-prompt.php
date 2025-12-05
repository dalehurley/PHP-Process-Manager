<?php

declare(strict_types=1);

/**
 * Worker script for processing a single Claude prompt.
 *
 * This script is spawned by the ProcessManager for each concurrent request.
 * It receives prompt data as a base64-encoded JSON argument.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Check for API key (optional - will use simulation if not set)
$apiKey = getenv('ANTHROPIC_API_KEY');
$useSimulation = !$apiKey || getenv('DEMO_MODE') === '1';

// Parse input
if ($argc < 2) {
    fwrite(STDERR, "Error: No prompt data provided\n");
    exit(1);
}

$encodedData = $argv[1];
$promptData = json_decode(base64_decode($encodedData), true);

if (!$promptData || !isset($promptData['prompt'])) {
    fwrite(STDERR, "Error: Invalid prompt data\n");
    exit(1);
}

try {
    // Initialize Claude client
    // Note: Requires claude-php/claude-php-sdk package
    if ($useSimulation || !class_exists('ClaudePhp\ClaudePhp')) {
        // Fallback for demo without SDK or API key
        $response = simulateClaudeResponse($promptData['prompt']);
    } else {
        $client = new \ClaudePhp\ClaudePhp(apiKey: $apiKey);

        $message = $client->messages()->create([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 256,
            'messages' => [
                ['role' => 'user', 'content' => $promptData['prompt']]
            ]
        ]);

        $response = $message->content[0]->text ?? '';
    }

    // Output result as JSON
    echo json_encode([
        'id' => $promptData['id'] ?? 'unknown',
        'response' => $response,
        'timestamp' => date('c'),
    ]);

    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Simulate Claude response for demo purposes when SDK is not installed.
 */
function simulateClaudeResponse(string $prompt): string
{
    // Simulate API latency
    usleep(random_int(500000, 1500000)); // 0.5-1.5 seconds

    $responses = [
        'summary' => 'Parallel processing enables multiple tasks to execute simultaneously, dramatically reducing total completion time. It maximizes resource utilization and is essential for modern high-performance applications.',
        'haiku' => "Threads dance together\nProcesses spin in concert\nSpeed through unity",
        'explanation' => 'A process pool is a collection of pre-initialized worker processes that stand ready to execute tasks. Instead of creating a new process for each task (which is expensive), tasks are distributed to available workers in the pool, reusing them for efficiency. This pattern reduces overhead and provides controlled concurrency.',
    ];

    // Try to match the prompt to a canned response
    foreach ($responses as $key => $response) {
        if (stripos($prompt, $key) !== false || stripos($prompt, str_replace('_', ' ', $key)) !== false) {
            return $response;
        }
    }

    return "This is a simulated response. Install claude-php/claude-php-sdk for real API calls.";
}

