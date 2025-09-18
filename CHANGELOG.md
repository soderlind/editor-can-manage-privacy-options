# Changelog# Changelog



All notable changes to this project will be documented in this file.All notable changes to this project will be documented in this file.



The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/).## [1.1.1] - 2025-09-18

- Improved duplicate menu handling and CSS injection

## [Unreleased]- Enhanced security checks for Editor role

- Add GitHub Actions for coding standards / linting- Code cleanup and documentation

- Add screenshots and WordPress.org readme.txt format (if published to .org)

## [1.0.0] - Initial release

## [1.1.1] - 2025-09-18- Editors can manage privacy options

### Added- Adds Privacy menu for Editors

- Defensive duplicate Privacy menu cleanup (`cleanup_duplicate_privacy_menu`).
- Multiple CSS injection hooks to handle edge cases in admin rendering.

### Changed
- Improved heuristic for distinguishing Editors from effectively admin-level users.
- Inline documentation and structural clarity.

### Fixed
- Prevents appearance of duplicate Privacy submenu entries for Editors.

## [1.0.0] - 2025-09-18
### Added
- Initial implementation: remaps `manage_privacy_options` for Editors.
- Adds Privacy submenu for Editors when not already exposed by core.
- Temporary request-scoped capability elevation on privacy pages.

[Unreleased]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.1.1...HEAD
[1.1.1]: https://github.com/soderlind/editor-can-manage-privacy-options/compare/v1.0.0...v1.1.1
