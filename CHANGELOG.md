# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

Mostly small coding standard (CS) related changes, with some improvements to method docblocks throughout.

### Added

  * Added codecov.io into workflow.
  * Added issue templates and a pull request template.
  * Added `backward-compatibility.md` for my backwards compatibility promise.
  * Added/updated Contributing information in `CONTRIBUTING.md`
  * Added a Contributor Code of Conduct in `CODE_OF_CONDUCT.md`
  * Added new dev dependencies: `vimeo/psalm` and `psalm/plugin-phpunit`.
  * Added new workflow `psalm.yml`.

### Changed

  * Updated `composer.lock`
  * Updated dev-dependencies.
  * Updated the header docblock in source files to be more compact, reduce filesize.
  * Updated coding standards via PHP-CS-Fixer and applied changes to source files.

### Fixed

  * Fixed code throughout per Psalm's reports.


## [2.0.1] - 2024-03-02

Mostly small coding standard (CS) related changes, with some improvements to method docblocks throughout.

### Added

  * Added validation to `Paginator`'s construct for the passed `$config` parameter.
    * Uses a new helper function `validateConfig()`, which is a static protected method.
  * Added some documentation/docblocks throughout, mostly to the `PaginatorInterface`.
  * Added `ext-pdo` and `ext-pdo_sqlite` to the composer require-dev.
  * Added the `Override` attribute to `Paginator` methods that are from `PaginatorInterface`.

### Changed

  * Bumped version to `2.0.1`.
  * Small change to how `$pagesInRange` within `paginate()` is determined.
  * (CS) Rearranged the order of methods within `Paginator` and `PaginatorInterface`.
  * Made the following helper functions static:
    * `determinePageRange()`
    * `determinePreviousPageNumber()`
    * `determineNextPageNumber()`
  * Updated `composer.lock`


## [2.0.0] - 2024-02-28

Forked from [`ashleydawson/simple-pagination`](https://github.com/AshleyDawson/SimplePagination) v1.0.8.

### Changed

  * Updated project namespaces to `Esi\Pagination`.
    * `lib` folder renamed to `src`
  * Refactored the `Paginator::paginate()` function to reduce it's complexity. Uses new helper functions.
  * Updated composer.json
    * Bumped minimum PHP version to 8.2
    * Autoloading should follow PSR-4
    * Updated PHPUnit to 11.0+
  * Updated unit tests, line coverage should now be 100%.
  * Cleaned up code and refactored to use newer PHP 8 features / conventions.
    * Should now adhere to PER and PSR-12 as well.
    * Now passes PHPStan using level 9 w/strict rules
  * Updated README.md

### Added

  * Helper functions to simplify the `Paginator::paginate()` function. Note: these have protected access.
    * `prepareBeforeQueryCallback()` - Handles either returning the beforeQueryCallback, or returning an empty \Closure.
    * `prepareAfterQueryCallback()` - Handles either returning the afterQueryCallback, or returning an empty \Closure.
    * `determinePageRange()` - Given `$currentPageNumber`, `$pagesInRange`, and `$numberOfPages`, returns an array of pages.
    * `determinePreviousPageNumber()`
    * `determineNextPageNumber()`
  * dev-dependencies for PHP-CS-Fixer and PHPStan (w/extensions for phpunit, strict rules)
  * Imports for all used functions, constants, and class names.
  * Github workflows for testing and static analysis.
  * Testing for DB related items (using SQLite via PDO and the [`factbook.db`](https://github.com/factbook/factbook.sql))
  * CHANGELOG.md, SECURITY.md

### TODO/WIP

  * Documentation improvements.

[unreleased]: https://github.com/ericsizemore/pagination/tree/master
[2.0.1]: https://github.com/ericsizemore/pagination/releases/tag/v2.0.1
[2.0.0]: https://github.com/ericsizemore/pagination/releases/tag/v2.0.0