## CHANGELOG
A not so exhaustive list of changes for each release.

For a more detailed listing of changes between each version, 
you can use the following url: https://github.com/ericsizemore/pagination/compare/v1.0.8...v2.0.0. 

Simply replace the version numbers depending on which set of changes you wish to see.


### 2.0.2 (work in progress)

Mostly small coding standard (CS) related changes, with some improvements to method docblocks throughout.

#### Changed

  * Updated `composer.lock`

#### Added

  * Added codecov.io into workflow.

#### Removed

  * None


### 2.0.1 (2024-03-02)

Mostly small coding standard (CS) related changes, with some improvements to method docblocks throughout.

#### Changed

  * Bumped version to `2.0.1`.
  * Small change to how `$pagesInRange` within `paginate()` is determined.
  * (CS) Rearranged the order of methods within `Paginator` and `PaginatorInterface`.
  * Made the following helper functions static:
    * `determinePageRange()`
    * `determinePreviousPageNumber()`
    * `determineNextPageNumber()`
  * Updated `composer.lock`

#### Added

  * Added validation to `Paginator`'s construct for the passed `$config` parameter.
    * Uses a new helper function `validateConfig()`, which is a static protected method.
  * Added some documentation/docblocks throughout, mostly to the `PaginatorInterface`.
  * Added `ext-pdo` and `ext-pdo_sqlite` to the composer require-dev.
  * Added the `Override` attribute to `Paginator` methods that are from `PaginatorInterface`.

#### Removed

  * None


### 2.0.0 (2024-02-28)

Forked from [`ashleydawson/simple-pagination`](https://github.com/AshleyDawson/SimplePagination) v1.0.8.

#### Changed

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

#### Added

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

#### Removed

  * N/A

#### TODO/WIP

  * Documentation improvements.