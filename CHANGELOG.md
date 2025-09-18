# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]
### Added
- (Planned) Additional automation or tooling improvements

### Security
- (Planned) Further hardening review

## [1.2.1] - 2025-09-18
### Changed
- Minor metadata bump (no functional code changes)

### Maintenance
- Prepare for future automation (placeholder)

## [1.2.0] - 2025-09-18
### Added
- WordPress.org compatible `readme.txt`
- Updated compatibility: WP 6.8, PHP 8.2

### Changed
- Bumped minimum WordPress requirement to 6.5
- Bumped minimum PHP requirement to 8.2

### Maintenance
- Documentation refinements (README normalization)

## [1.1.1] - 2025-09-18
### Added
- Defensive duplicate Privacy menu cleanup (`cleanup_duplicate_privacy_menu`)
- Multiple CSS injection hooks to handle edge cases in admin rendering

### Changed
- Improved heuristic for distinguishing Editors from effectively admin-level users
- Documentation and internal code comments clarified

### Fixed
- Prevent duplicate Privacy submenu entries for Editors

## [1.0.0] - 2025-09-18
### Added
- Initial implementation: remaps `manage_privacy_options` for Editors
- Adds Privacy submenu for Editors when not already exposed by core
- Temporary request-scoped capability elevation on privacy pages

[Unreleased]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.1.1...v1.2.0
[1.1.1]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.0.0...v1.1.1
