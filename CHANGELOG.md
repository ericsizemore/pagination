# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [2.1.1] - 2026-02-14

### Changed

  * Updated the `scripts` within `composer.json`.
  * Updated the CI on how it handles the composer lock file and dependencies on install.
  * Updated the `PHP` requirement in `composer.json` to allow PHP 8.5.
  * Updated dev-dependencies to allow PHPUnit 11, 12 or 13.
  * [fix] PHPStan reported issues in `Paginator`.
  * Updated `psalm.xml` to suppress certain issues until the minimum PHP requirement, etc. is updated.
  * Added `#[\Override]` where needed.
  * Updated various documentation/guidelines:
    * [Backward Compatibility](backward-compatibility.md)
    * [Contributing](CONTRIBUTING.md)
    * [Security Policy](SECURITY.md)


## [2.1.0] - 2025-01-20

### Changed

  * Several updates throughout to bring this repository more in line with the project template I normally use.
    * Updated:
      * CONTRIBUTING.md — updated to use my most current project template contributing information.
      * README.md — updated to more closely follow my preferred layout for README files.
      * composer.json — updated dependencies and modified the `scripts` to more closely match my project template.
  * Merged the `main`, `psalm`, and `tests` workflows in `.github/workflows` into one workflow file `continuous-integration.yml`.
  * Various updates to PHPDocs and code, throughout the entire library, to improve type safety and coverage as much as possible.
    *  This includes updating PHPDoc blocks in the PaginatorInterface to provide clarification on the expected `Closure` signatures 
       for the `itemTotalCallback` and `sliceCallback` callbacks. Further documentation for this is on the TODO list.

### Added

  * Added `rector` as a dev-dependency.
    * Adds `rector.php` in the main repo for Rector configuration.
  * Added a new `release` workflow to `.github/workflows` to allow automatic creation of a release when a tag is pushed.

### Removed

  * Baseline files for Psalm and PHPStan.


## [2.0.2] - 2024-09-26

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

  * Bumped version to `2.0.2`.
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
  * Refactored the `Paginator::paginate()` function to reduce its complexity. Uses new helper functions.
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
  * GitHub workflows for testing and static analysis.
  * Testing for DB related items (using SQLite via PDO and the [`factbook.db`](https://github.com/factbook/factbook.sql))
  * CHANGELOG.md, SECURITY.md

### TODO/WIP

  * Documentation improvements.

[unreleased]: https://github.com/ericsizemore/pagination/tree/master
[2.1.1]: https://github.com/ericsizemore/pagination/releases/tag/v2.1.1
[2.1.0]: https://github.com/ericsizemore/pagination/releases/tag/v2.1.0
[2.0.2]: https://github.com/ericsizemore/pagination/releases/tag/v2.0.2
[2.0.1]: https://github.com/ericsizemore/pagination/releases/tag/v2.0.1
[2.0.0]: https://github.com/ericsizemore/pagination/releases/tag/v2.0.0
