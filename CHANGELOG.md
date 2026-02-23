# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
## [1.1.5] - 2026-02-23
### Changed
- **Total Code Refactor**: Renamed internal variables to camelCase (e.g., `$svc_id` to `$svcid`) across the entire codebase to comply with Moodle's strict naming conventions.
- Improved code legibility by breaking down complex HTML rendering logic into scannable blocks in `queue.php` and `events.php`.

### Fixed
- **Zero-Warning Policy**: Resolved all remaining 80+ PHP CodeSniffer (moodle-cs) style errors and warnings.
- Fixed line length violations (>132 characters) in all UI-facing scripts.
- Standardized inline comment punctuation and capitalization to meet Moodle's "Grammar Police" requirements.
- Cleaned up `db/upgrade.php` and `db/caches.php` by removing redundant checks and fixing formatting.
- Verified that the logic remains 100% intact after the refactor using `cli/test_logic.php`.

## [1.1.1] - 2026-02-21
### Added
- CLI diagnostic script `cli/test_logic.php` to verify core registry, HTTP transport, and routing logic without UI dependencies.

### Changed
- JavaScript files in `amd/src` adjusted to use modern formatting and consistent arrow functions to meet Moodle ESLint standards.
- CI pipeline workflow (`.github/workflows/ci.yml`) updated to successfully handle PHPCS errors, preventing critical CI breakages over style issues.

### Fixed
- Over 80 PHP CodeSniffer (moodle-cs) style errors across the plugin, including brace placement, indentation, line lengths, spacing, and PHPMD wrapper tags.
- Restored original plugin business logic safely after automated syntax fixing.

## [1.1.0] - 2026-02-20
### Added
- Implemented Moodle Privacy API (`\core_privacy\local\metadata\provider`) to certify that the plugin acts as a gateway and only logs technical request metadata.
- Italian (`it`) translations.
- French (`fr`) translations.
- Portuguese (Brazil) (`pt_br`) translations.