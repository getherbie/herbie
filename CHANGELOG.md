# Change Log
All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed
- **Breaking:** Minimum PHP version raised from 8.0 to 8.3. Supported versions: 8.3, 8.4, 8.5, 8.6.
- Version bumped to 3.0.0 (new major line).
- `time_format()` now uses PHP's `date()` format syntax instead of the deprecated `strftime()` (strftime removed in PHP 9.0). **Breaking:** format strings must be migrated from strftime to date() syntax.
- PSR-17 factory selection in `ContainerBuilder` deduplicated via `preferredPsr17Package()`.
- `UTF8_BOM` constant is now defined at file scope in `FlatFilePagePersistence`.

### Fixed
- `UrlManager::parseRequest()` now caches the parsed request when a route rule matches.
- Cache directory is created with mode 0755 instead of 0644.

### Security
- `FileCache` restricts deserialization to scalar/array values (`allowed_classes => false`).
- `handle_internal_webserver_assets()` prevents path traversal outside the base directory.
- `FlatFilePagePersistence::findById()` restricts access to the page root.

## [2.2.0] - 2025-05-23

### Added
- New `twigify` Twig filter to render a string with Twig
- New `twigify` Twig function to render a string with Twig

## [2.1.0] - 2025-04-13

### Added
- Support for PHP 8.3
- Support for Docker and Docker Compose

### Fixed
- Failed WebsiteCest

## [2.0.0] - 2023-01-02

See https://github.com/getherbie/herbie/issues/82.

## [1.1.0] - 2018-10-27
- Remove Composer minimum-stability
- Bump php version requirement to 5.6
- Fix phpcs code issues

## [1.0.1] - 2016-09-24
### Added
- CHANGELOG.md
- Throw Exception if plugins path not exist
- Moved config, messages, plugins and system folders to src folder

## [1.0.0] - 2016-03-25
- Release of Version 1.0.0

---

`Added` for new features.  
`Changed` for changes in existing functionality.  
`Deprecated` for once-stable features removed in upcoming releases.  
`Removed` for deprecated features removed in this release.  
`Fixed` for any bug fixes.  
`Security` to invite users to upgrade in case of vulnerabilities.

---

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

[Unreleased]: https://github.com/getherbie/herbie/compare/v2.2.0...HEAD
[2.2.0]: https://github.com/getherbie/herbie/compare/v2.1.0...v2.2.0
[2.1.0]: https://github.com/getherbie/herbie/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/getherbie/herbie/compare/v1.1.0...v2.0.0
[1.1.0]: https://github.com/getherbie/herbie/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/getherbie/herbie/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/getherbie/herbie/compare/v0.5.0...v1.0.0
