# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2025-12-05

### Added

- Complete rewrite for PHP 8.2+ with modern features
- Namespace `DaleHurley\ProcessManager` with PSR-4 autoloading
- `ProcessManager` class with fluent API for configuration
- `Process` class with typed properties and comprehensive process control
- `ProcessResult` readonly class for detailed execution results
- `OutputHandlerInterface` for pluggable output handling
- `ConsoleOutputHandler` for CLI output with optional ANSI colors
- `HtmlOutputHandler` for web-based output with HTML formatting
- `NullOutputHandler` for silent execution
- Custom exception classes (`ProcessException`, `ProcessStartException`, `ProcessTimeoutException`)
- Support for command-line arguments per script
- Support for environment variables per script
- Process timeout detection and automatic termination
- Exit code and output capture (stdout/stderr)
- Elapsed time tracking per process
- Comprehensive test suite with PHPUnit
- PHPStan level 8 static analysis
- Composer scripts for testing and analysis

### Changed

- **Breaking:** Renamed class `Processmanager` to `ProcessManager`
- **Breaking:** Renamed method `exec()` to `run()`
- **Breaking:** Renamed property `root` to `workingDirectory`
- **Breaking:** Renamed property `processes` to `maxConcurrentProcesses`
- **Breaking:** Renamed property `sleep_time` to `sleepInterval`
- **Breaking:** `run()` now returns array of `ProcessResult` objects instead of void
- **Breaking:** Output handling moved from boolean flag to dedicated handler classes
- Minimum PHP version raised to 8.2

### Removed

- **Breaking:** Removed `show_output` property (use output handlers instead)
- **Breaking:** Removed direct property assignment (use constructor or setters)
- Legacy PHP 5.x compatibility

## [1.0.0] - 2012-01-01

### Added

- Initial release
- Basic multi-process execution with `proc_open`
- Configurable concurrent process limit
- Maximum execution time per script
- Optional HTML output display

---

[Unreleased]: https://github.com/dalehurley/PHP-Process-Manager/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/dalehurley/PHP-Process-Manager/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/dalehurley/PHP-Process-Manager/releases/tag/v1.0.0

