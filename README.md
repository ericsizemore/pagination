Pagination
==========

[![Build Status](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/?branch=master)
[![Continuous Integration](https://github.com/ericsizemore/pagination/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/ericsizemore/pagination/actions/workflows/continuous-integration.yml)
[![Type Coverage](https://shepherd.dev/github/ericsizemore/pagination/coverage.svg)](https://shepherd.dev/github/ericsizemore/pagination)
[![Psalm Level](https://shepherd.dev/github/ericsizemore/pagination/level.svg)](https://shepherd.dev/github/ericsizemore/pagination)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=ericsizemore_pagination&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=ericsizemore_pagination)
[![Latest Stable Version](https://img.shields.io/packagist/v/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)
[![Downloads per Month](https://img.shields.io/packagist/dm/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)
[![License](https://img.shields.io/packagist/l/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)

`Esi\Pagination` - A library that implements a paging interface on collections of things.

## Installation

`Esi\Pagination` can be installed via [Composer](https://getcomposer.org/):

```bash
$ composer require esi/pagination:^2.1
```

## How Pagination Works

I have tried to make Pagination as simple, flexible, and easy to use as possible. There are four main elements that
describe the operation of Pagination. These are:

* Paginator service
* Item total callback
* Slice callback
* Pagination model

The **Paginator** service performs the pagination algorithm, generating the page range and item collection slices.
When it is done, it will return a **Pagination** object filled with the item collection slice and metadata.

The two main operations the **Paginator** service will perform on your collection (or data set) are denoted by two
callback methods passed to the **Paginator** service. The first one is the **Item total callback**. This callback is
used to determine the total amount of items in your collection (returned as an integer). The second one is the 
**Slice callback**. This callback actually slices your collection given an **offset** and **length** argument.

The idea behind using these callbacks is so that Pagination is kept, well, simple! The real power comes with
the flexibility. You can use Pagination with just about any collection you want. From simple arrays to database
lists to [Doctrine](http://www.doctrine-project.org/) collections to [Solr](http://lucene.apache.org/solr/) result sets — we have you covered! It really doesn't matter what 
we paginate — as long as it is a collection of things, and you can count and slice it.

### Basic Usage

First, the most basic example — paginating over an array.

```php
use Esi\Pagination\Paginator;

use function array_slice;
use function filter_input;

use const FILTER_VALIDATE_INT;
use const INPUT_GET;

// Build a mock list of items we want to paginate through.
$items = [
    'Banana',
    'Apple',
    'Cherry',
    'Lemon',
    'Pear',
    'Watermelon',
    'Orange',
    'Grapefruit',
    'Blackcurrant',
    'Dingleberry',
    'Snosberry',
    'Tomato',
];

// Instantiate a new paginator service.
$paginator = new Paginator();

// Set some parameters (optional).
$paginator
    ->setItemsPerPage(10) // Give us a maximum of 10 items per page.
    ->setPagesInRange(5)  // How many pages to display in navigation (e.g. if we have a lot of pages to get through).
;

// Pass our item total callback.
$paginator->setItemTotalCallback(static function () use ($items): int {
    return \count($items);
});

// Pass our slice callback.
$paginator->setSliceCallback(static function (int $offset, int $length) use ($items): array {
    return array_slice($items, $offset, $length);
});

// Paginate the collection, passing the current page number (e.g., from the current request).
$pagination = $paginator->paginate(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

// Ok, from here on is where we would be inside a template or view (e.g., pass $pagination to your view).

// Iterate over the items on this page.
foreach ($pagination->getItems() as $item) {
    echo \sprintf('%s<br />', $item);
}

// Build a basic page navigation structure.
foreach ($pagination->getPages() as $page) {
    echo \sprintf('<a href="?page=%1$d">%1$d</a> ', $page);
}
```

There are lots of other pieces of metadata held within the [pagination object](#pagination-object). These can be used for building
first, last, previous and next buttons.

### MySQL Example

Now take the example above and use a MySQL result set instead of an array.

```php
use Esi\Pagination\Paginator;
use mysqli;

use function filter_input;

use const FILTER_VALIDATE_INT;
use const INPUT_GET;

// Instantiate a new paginator service.
$paginator = new Paginator();

// Set some parameters (optional).
$paginator
    ->setItemsPerPage(10) // Give us a maximum of 10 items per page.
    ->setPagesInRange(5)  // How many pages to display in navigation (e.g., if we have a lot of pages to get through).
;

// Connect to a database.
$mysql = new mysqli('localhost', 'root', 'password', 'myDatabase');

// Pass our item total callback.
$paginator->setItemTotalCallback(static function () use($mysql): int {
    // Run count query.
    $result = $mysql->query("SELECT COUNT(*) AS `totalCount` FROM `TestData`");
    $row = $result->fetch_array(MYSQLI_ASSOC);
    
    // Return the count, cast as an integer.
    return (int) $row['totalCount'];
});

// Pass our slice callback.
$paginator->setSliceCallback(static function (int $offset, int $length) use ($mysql): array {
    // Run slice query.
    $result = $mysql->query("SELECT `Name` FROM `TestData` LIMIT $offset, $length");

    // Build a collection of items.
    $collection = [];

    while ($row = $result->fetch_assoc()) {
        $collection[] = $row;
    }
    
    // Return the collection.
    return $collection;
});

// Paginate the item collection, passing the current page number (e.g. from the current request).
$pagination = $paginator->paginate(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

// Ok, from here on is where we'd be inside a template of view (e.g. pass $pagination to your view).

// Iterate over the items on this page.
foreach ($pagination->getItems() as $item) {
    echo sprintf('%s<br />', $item['Name']);
}

// Let's build a basic page navigation structure.
foreach ($pagination->getPages() as $page) {
    echo \sprintf('<a href="?page=%1$d">%1$d</a> ', $page);
}
```

**Note:** The example above uses `mysqli` as I tried to make it as simple as possible. In the real world please use [PDO](https://www.php.net/pdo), 
[Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html), etc.

It really does not matter what sort of collection you return from the `Paginator::setSliceCallback()` callback. It will always end up in `Pagination::getItems()`.

### Constructor Configuration

You can also configure the paginator with a configuration array passed to the constructor. For example:

```php
$paginator = new Paginator([
    'itemTotalCallback' => static function (): int {
        // ...
    },
    'sliceCallback' => static function (int $offset, int $length): array {
        // ...
    },
    'itemsPerPage' => 10,
    'pagesInRange' => 5,
]);
```

### Pagination as an Iterator

The `Pagination` object returned from the `Paginator` service implements `\IteratorAggregate` and `\Countable` so you can do things like this in your view:

```php
if (\count($pagination) > 0) {
    foreach ($pagination as $item) {
        echo \sprintf('%s<br />', $item);
    }
}
```

### Arbitrary Pagination Metadata

In `itemTotalCallback` and `sliceCallback`, you have the option of passing arbitrary metadata to the pagination object. This is an optional feature
and is useful if you have a use-case where additional data is returned by these operations, and you want to access it from the pagination object 
whilst listing the items. A good example of this is when using search engines such as [ElasticSearch](https://www.elastic.co/elasticsearch), you 
can pass back secondary information — like aggregations, etc. A generic example can be seen below:

```php
use Esi\Pagination\Pagination;

use function array_slice;
use function filter_input;
use function var_dump;

use const FILTER_VALIDATE_INT;
use const INPUT_GET;

// ...

$paginator->setItemTotalCallback(static function (Pagination $pagination) use ($items): int {
    // Pass arbitrary metadata to pagination object.
    $pagination->setMeta(['my', 'meta', 'data']);
    
    return \count($items);
});

$paginator->setSliceCallback(static function (int $offset, int $length, Pagination $pagination) use ($items): array {
    // Pass more arbitrary metadata to pagination object.
    $pagination->setMeta(array_merge($pagination->getMeta(), ['more', 'stuff']));

    return array_slice($items, $offset, $length);
});

// ...

// Perform the pagination
$pagination = $paginator->paginate(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

// Get the metadata from the pagination object.
var_dump($pagination->getMeta());
```

### Pre-Query and Post-Query Callbacks

Before and after the count and slice queries, you can set callbacks to fire. To set them, do the following:

```php
$paginator->setBeforeQueryCallback(static function (Paginator $paginator, Pagination $pagination): void {
    // ...
});

$paginator->setAfterQueryCallback(static function (Paginator $paginator, Pagination $pagination): void {
    // ...
});
```

This is handy if you want to perform some function or process, such as adding/removing meta data, before and after each query is made.

### Pagination Object

`Paginator::paginate()` will return a `Pagination` model object, which carries the item collection for the current page plus the meta 
information for the collection. For e.g., the pages array, next page number, previous page number, etc.

Please see below for a list of properties that the `Pagination` object has.

* **items** : array (Collection of items for the current page)
* **pages** : array (Array of page numbers in the current range)
* **totalNumberOfPages** : int (Total number of pages)
* **currentPageNumber** : int (Current page number)
* **firstPageNumber** : int (First page number)
* **lastPageNumber** : int (Last page number)
* **previousPageNumber** : int | null (Previous page number)
* **nextPageNumber** : int | null (Next page number)
* **itemsPerPage** : int (Number of items per page)
* **totalNumberOfItems** : int (Total number of items)
* **firstPageNumberInRange** : int (First page number in current range)
* **lastPageNumberInRange** : int (Last page number in current range)

A good example of using the `Pagination` object is to build a simple pagination navigation structure:

```php
// Render the first page link,
echo \sprintf('<a href="?page=%d">First Page</a> ', $pagination->getFirstPageNumber());

// Render the previous page link (note: the previous page number could be null),
echo \sprintf('<a href="?page=%d">Previous Page</a> ', $pagination->getPreviousPageNumber());

// Render page range links,
foreach ($pagination->getPages() as $page) {
    echo \sprintf('<a href="?page=%1$d">%1$d</a> ', $page);
}

// Render the next page link (note: the next page number could be null),
echo \sprintf('<a href="?page=%d">Next Page</a> ', $pagination->getNextPageNumber());

// Render the last page link,
echo \sprintf('<a href="?page=%d">Last Page</a>', $pagination->getLastPageNumber());
```

## About

### Requirements

- Pagination works with PHP 8.2.0 or above.

### Credits

This library is a `fork` of the [AshleyDawson\SimplePagination](https://github.com/AshleyDawson/SimplePagination) library by [Ashley Dawson](https://github.com/AshleyDawson).

To view changes in this library in comparison to the original library, please see the [CHANGELOG.md](./CHANGELOG.md) file.

- Author: [Eric Sizemore](https://github.com/ericsizemore)
- Thanks to [Ashley Dawson](https://github.com/AshleyDawson) for the original [AshleyDawson\SimplePagination](https://github.com/AshleyDawson/SimplePagination) library that this library is forked from.
- Thanks to [all Contributors](https://github.com/ericsizemore/pagination/contributors).
- Special thanks to [JetBrains](https://www.jetbrains.com/?from=esi-pagination) for their Licenses for Open Source Development.

### Contributing

See [CONTRIBUTING](./CONTRIBUTING.md).

Bugs and feature requests are tracked on [GitHub](https://github.com/ericsizemore/pagination/issues).

### Contributor Covenant Code of Conduct

See [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md)

### Backward Compatibility Promise

See [backward-compatibility.md](./backward-compatibility.md) for more information on Backwards Compatibility.

### Changelog

See the [CHANGELOG](./CHANGELOG.md) for more information on what has changed recently.

### License

See the [LICENSE](./LICENSE.md) for more information on the license that applies to this project.

### Security

See [SECURITY](./SECURITY.md) for more information on the security disclosure process.
