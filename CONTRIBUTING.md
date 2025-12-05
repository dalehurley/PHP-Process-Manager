# Contributing to PHP Process Manager

Thank you for considering contributing to PHP Process Manager! This document outlines the process for contributing to this project.

## Code of Conduct

Please be respectful and constructive in all interactions. We're all here to build something useful together.

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report, please check existing issues to avoid duplicates.

When reporting a bug, include:

- **PHP version** (`php -v`)
- **Package version** (check `composer.json` or `composer show dalehurley/process-manager`)
- **Operating system**
- **Steps to reproduce** the issue
- **Expected behavior** vs **actual behavior**
- **Error messages** or stack traces if applicable
- **Minimal code example** that reproduces the issue

### Suggesting Features

Feature requests are welcome! Please:

- Check existing issues first
- Clearly describe the use case
- Explain why existing functionality doesn't meet your needs
- Consider if this fits the project's scope (lightweight, simple process management)

### Pull Requests

1. **Fork the repository** and create your branch from `master`
2. **Install dependencies**: `composer install`
3. **Make your changes**
4. **Add tests** for any new functionality
5. **Run the test suite**: `composer check`
6. **Update documentation** if needed
7. **Submit your pull request**

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/PHP-Process-Manager.git
cd PHP-Process-Manager

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Run all checks
composer check
```

## Coding Standards

This project follows:

- **PSR-12** coding style
- **PSR-4** autoloading
- **Strict types** (`declare(strict_types=1)`)
- **PHP 8.2+** features (readonly classes, constructor promotion, named arguments)

### Code Style Guidelines

```php
<?php

declare(strict_types=1);

namespace DaleHurley\ProcessManager;

/**
 * Brief description of the class.
 */
final class ExampleClass
{
    public function __construct(
        private readonly string $property
    ) {
    }

    /**
     * Brief description of what this method does.
     */
    public function exampleMethod(string $param): bool
    {
        // Implementation
        return true;
    }
}
```

### Key Conventions

- Use `final` classes unless inheritance is intended
- Use `readonly` properties where appropriate
- Add PHPDoc blocks for public methods
- Use type hints for all parameters and return types
- Prefer named arguments for clarity in constructors

## Testing

All new features and bug fixes should include tests.

```bash
# Run all tests
composer test

# Run specific test file
./vendor/bin/phpunit tests/ProcessManagerTest.php

# Run with coverage (requires Xdebug or PCOV)
composer test:coverage
```

### Test Structure

```
tests/
├── Exception/           # Exception class tests
├── Output/              # Output handler tests
├── fixtures/            # Test scripts (success.php, failure.php, etc.)
├── ProcessManagerTest.php
├── ProcessResultTest.php
└── ProcessTest.php
```

## Static Analysis

We use PHPStan at level 8 (strictest):

```bash
composer analyse
```

All code must pass analysis with no errors before merging.

## Commit Messages

Use clear, descriptive commit messages:

```
Add timeout handling for long-running processes

- Implement hasExceededTimeout() method in Process class
- Add automatic termination in ProcessManager run loop
- Add tests for timeout scenarios
```

Format:
- First line: Brief summary (50 chars or less)
- Blank line
- Body: Detailed explanation if needed

## Versioning

This project follows [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

## Questions?

Feel free to open an issue for questions or discussion. We're happy to help!

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

