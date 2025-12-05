<?php

declare(strict_types=1);

/**
 * Worker script for processing Claude tool calls.
 *
 * This demonstrates how to use Claude's tool/function calling feature
 * within a parallel processing context.
 */

// Use local vendor if available (for Claude SDK), fallback to root
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Check for API key (optional - will use simulation if not set)
$apiKey = getenv('ANTHROPIC_API_KEY');
$useSimulation = !$apiKey || getenv('DEMO_MODE') === '1';

// Parse input
if ($argc < 2) {
    fwrite(STDERR, "Error: No task data provided\n");
    exit(1);
}

$taskData = json_decode(base64_decode($argv[1]), true);

if (!$taskData || !isset($taskData['prompt'])) {
    fwrite(STDERR, "Error: Invalid task data\n");
    exit(1);
}

// Define available tools
$tools = [
    'get_weather' => [
        'name' => 'get_weather',
        'description' => 'Get the current weather for a location',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'location' => [
                    'type' => 'string',
                    'description' => 'City name, e.g., San Francisco, CA',
                ],
                'unit' => [
                    'type' => 'string',
                    'enum' => ['celsius', 'fahrenheit'],
                    'description' => 'Temperature unit',
                ],
            ],
            'required' => ['location'],
        ],
    ],
    'calculate' => [
        'name' => 'calculate',
        'description' => 'Perform mathematical calculations',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'Mathematical expression to evaluate',
                ],
            ],
            'required' => ['expression'],
        ],
    ],
    'extract_contact' => [
        'name' => 'extract_contact',
        'description' => 'Extract contact information from text',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'phone' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ],
    ],
];

try {
    // Check if Claude SDK is available and we have an API key
    if ($useSimulation || !class_exists('ClaudePhp\ClaudePhp')) {
        // Fallback simulation for demo
        $result = simulateToolCall($taskData);
        echo json_encode($result);
        exit(0);
    }

    // Real Claude API call with tools
    $client = new \ClaudePhp\ClaudePhp(apiKey: $apiKey);

    // Select the appropriate tool
    $toolName = $taskData['tool'] ?? 'get_weather';
    $toolDef = $tools[$toolName] ?? $tools['get_weather'];

    $message = $client->messages()->create([
        'model' => 'claude-sonnet-4-20250514',
        'max_tokens' => 1024,
        'tools' => [$toolDef],
        'messages' => [
            ['role' => 'user', 'content' => $taskData['prompt']]
        ]
    ]);

    // Process tool use response
    $toolResult = null;
    $claudeResponse = '';

    foreach ($message->content as $block) {
        // Handle both object and array format
        $blockType = is_array($block) ? ($block['type'] ?? '') : ($block->type ?? '');
        
        if ($blockType === 'tool_use') {
            // Extract tool use data
            $toolName = is_array($block) ? $block['name'] : $block->name;
            $toolInput = is_array($block) ? $block['input'] : $block->input;
            $toolId = is_array($block) ? $block['id'] : $block->id;
            
            // Execute the tool
            $toolResult = executeLocalTool($toolName, $toolInput);

            // Get Claude's interpretation by sending tool result back
            $followUp = $client->messages()->create([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 256,
                'tools' => [$toolDef],
                'messages' => [
                    ['role' => 'user', 'content' => $taskData['prompt']],
                    ['role' => 'assistant', 'content' => $message->content],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'tool_result',
                                'tool_use_id' => $toolId,
                                'content' => json_encode($toolResult),
                            ]
                        ]
                    ]
                ]
            ]);

            foreach ($followUp->content as $followBlock) {
                $followType = is_array($followBlock) ? ($followBlock['type'] ?? '') : ($followBlock->type ?? '');
                if ($followType === 'text') {
                    $claudeResponse = is_array($followBlock) ? $followBlock['text'] : $followBlock->text;
                    break;
                }
            }
        } elseif ($blockType === 'text') {
            $claudeResponse = is_array($block) ? $block['text'] : $block->text;
        }
    }

    echo json_encode([
        'id' => $taskData['id'],
        'tool' => $toolName,
        'tool_result' => $toolResult,
        'claude_response' => $claudeResponse,
    ]);

    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

/**
 * Execute a local tool function.
 */
function executeLocalTool(string $name, array $input): array
{
    return match ($name) {
        'get_weather' => getWeather($input),
        'calculate' => calculate($input),
        'extract_contact' => extractContact($input),
        default => ['error' => 'Unknown tool'],
    };
}

/**
 * Simulated weather tool.
 */
function getWeather(array $input): array
{
    $location = $input['location'] ?? 'Unknown';
    $unit = $input['unit'] ?? 'fahrenheit';

    // Simulated weather data
    $conditions = ['sunny', 'cloudy', 'rainy', 'partly cloudy'];
    $temp = $unit === 'celsius' ? random_int(10, 30) : random_int(50, 85);

    return [
        'location' => $location,
        'temperature' => $temp,
        'unit' => $unit,
        'conditions' => $conditions[array_rand($conditions)],
        'humidity' => random_int(30, 80) . '%',
    ];
}

/**
 * Calculator tool.
 */
function calculate(array $input): array
{
    $expression = $input['expression'] ?? '0';

    // Simple safe evaluation for demo (in production, use a proper math parser)
    $expression = preg_replace('/[^0-9+\-*\/\.\(\)\s%]/', '', $expression);

    // Handle percentage
    if (preg_match('/(\d+(?:\.\d+)?)\s*%\s*(?:of|on)?\s*(\d+(?:\.\d+)?)/', $input['expression'] ?? '', $matches)) {
        $percentage = (float) $matches[1];
        $base = (float) $matches[2];
        $result = ($percentage / 100) * $base;
    } else {
        // Basic eval for simple expressions
        try {
            $result = @eval("return {$expression};") ?? 0;
        } catch (Throwable) {
            $result = 0;
        }
    }

    return [
        'expression' => $input['expression'] ?? $expression,
        'result' => round($result, 2),
    ];
}

/**
 * Contact extraction tool.
 */
function extractContact(array $input): array
{
    return [
        'name' => $input['name'] ?? null,
        'email' => $input['email'] ?? null,
        'phone' => $input['phone'] ?? null,
    ];
}

/**
 * Simulate tool call for demo without SDK.
 */
function simulateToolCall(array $taskData): array
{
    // Simulate API latency
    usleep(random_int(500000, 1500000));

    $toolName = $taskData['tool'] ?? 'get_weather';

    // Parse the prompt to extract tool inputs
    $prompt = $taskData['prompt'];
    $toolResult = match ($toolName) {
        'get_weather' => getWeather([
            'location' => extractLocation($prompt),
            'unit' => 'fahrenheit',
        ]),
        'calculate' => calculate([
            'expression' => $prompt,
        ]),
        'extract_contact' => extractContact([
            'name' => extractName($prompt),
            'email' => extractEmail($prompt),
            'phone' => extractPhone($prompt),
        ]),
        default => ['error' => 'Unknown tool'],
    };

    return [
        'id' => $taskData['id'],
        'tool' => $toolName,
        'tool_result' => $toolResult,
        'claude_response' => generateToolResponse($toolName, $toolResult),
    ];
}

function extractLocation(string $text): string
{
    $cities = ['San Francisco', 'New York', 'London', 'Paris', 'Tokyo'];
    foreach ($cities as $city) {
        if (stripos($text, $city) !== false) {
            return $city;
        }
    }
    return 'Unknown City';
}

function extractName(string $text): ?string
{
    if (preg_match('/([A-Z][a-z]+ [A-Z][a-z]+)/', $text, $matches)) {
        return $matches[1];
    }
    return null;
}

function extractEmail(string $text): ?string
{
    if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/', $text, $matches)) {
        return $matches[0];
    }
    return null;
}

function extractPhone(string $text): ?string
{
    if (preg_match('/\+?[\d\-\(\)\s]{10,}/', $text, $matches)) {
        return trim($matches[0]);
    }
    return null;
}

function generateToolResponse(string $tool, array $result): string
{
    return match ($tool) {
        'get_weather' => sprintf(
            "The weather in %s is currently %s with a temperature of %d°%s and %s humidity.",
            $result['location'],
            $result['conditions'],
            $result['temperature'],
            $result['unit'] === 'celsius' ? 'C' : 'F',
            $result['humidity']
        ),
        'calculate' => sprintf(
            "The result of %s is %s.",
            $result['expression'],
            $result['result']
        ),
        'extract_contact' => sprintf(
            "Extracted contact: %s (%s, %s)",
            $result['name'] ?? 'N/A',
            $result['email'] ?? 'N/A',
            $result['phone'] ?? 'N/A'
        ),
        default => 'Tool executed successfully.',
    };
}

