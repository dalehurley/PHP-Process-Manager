# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

## Reporting a Vulnerability

If you discover a security vulnerability within PHP Process Manager, please follow these steps:

1. **Do not** disclose the vulnerability publicly until it has been addressed
2. **Email** the maintainer directly at security@dalehurley.com (or create a private security advisory on GitHub)
3. **Include** as much information as possible:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

## Response Timeline

- **Initial response**: Within 48 hours
- **Status update**: Within 7 days
- **Fix timeline**: Depends on severity, typically within 30 days for critical issues

## Security Best Practices

When using PHP Process Manager:

### Input Validation

Always validate and sanitize any user input before passing it to scripts:

```php
// ❌ Dangerous - user input directly in script name
$manager->addScript($_GET['script']);

// ✅ Safe - whitelist allowed scripts
$allowedScripts = ['import.php', 'export.php', 'cleanup.php'];
$script = $_GET['script'];

if (in_array($script, $allowedScripts, true)) {
    $manager->addScript($script);
}
```

### Command Injection Prevention

Be careful with dynamic arguments:

```php
// ❌ Dangerous - unsanitized user input in arguments
$manager->addScript('worker.php', arguments: [$_GET['file']]);

// ✅ Safe - validate and sanitize
$file = basename($_GET['file']); // Remove path traversal
if (preg_match('/^[\w\-]+\.csv$/', $file)) {
    $manager->addScript('worker.php', arguments: [$file]);
}
```

### Working Directory

Restrict the working directory to prevent path traversal:

```php
// ✅ Use absolute paths
$manager->setWorkingDirectory('/var/app/scripts');

// ✅ Validate script names don't contain path traversal
$script = basename($scriptName);
```

### Resource Limits

Set appropriate timeouts to prevent resource exhaustion:

```php
$manager->addScript('task.php', maxExecutionTime: 60); // Kill after 60 seconds
$manager->setMaxConcurrentProcesses(5); // Limit parallel processes
```

## Acknowledgments

We appreciate responsible disclosure and will acknowledge security researchers who report valid vulnerabilities (unless they prefer to remain anonymous).

