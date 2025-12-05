# Claude SDK Integration Examples

These examples demonstrate how to use PHP Process Manager with the [Claude PHP SDK](https://github.com/claude-php/Claude-PHP-SDK) for parallel AI processing.

## Prerequisites

Install the Claude PHP SDK:

```bash
composer require claude-php/claude-php-sdk
```

Set your API key:

```bash
export ANTHROPIC_API_KEY="your-api-key-here"
```

## Examples

### 1. Concurrent API Calls

Run multiple Claude prompts simultaneously:

```bash
php concurrent-prompts.php
```

This example processes 3 different prompts in parallel, reducing total time from ~9 seconds (sequential) to ~3 seconds.

### 2. Parallel Tool Calls

Execute tool-based tasks concurrently:

```bash
php parallel-tools.php
```

This example runs multiple tool-calling workflows in parallel - useful for data extraction, analysis pipelines, or multi-agent systems.

## How It Works

Instead of calling Claude sequentially:

```
Prompt 1 (3s) → Prompt 2 (3s) → Prompt 3 (3s) = 9 seconds total
```

Process Manager runs them in parallel:

```
Prompt 1 (3s) ─┐
Prompt 2 (3s) ─┼─ = 3 seconds total
Prompt 3 (3s) ─┘
```

## Use Cases

- **Batch content generation** - Generate multiple articles/summaries simultaneously
- **Multi-language translation** - Translate to several languages at once
- **Data extraction pipelines** - Extract structured data from multiple documents
- **Parallel analysis** - Run sentiment analysis, classification, or summarization in parallel
- **Multi-agent workflows** - Coordinate multiple AI agents working on subtasks

