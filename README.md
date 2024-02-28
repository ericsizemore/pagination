Pagination
==========

[![Build Status](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/ericsizemore/pagination/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/ericsizemore/pagination/?branch=master)
[![Tests](https://github.com/ericsizemore/pagination/actions/workflows/tests.yml/badge.svg)](https://github.com/ericsizemore/pagination/actions/workflows/tests.yml)
[![PHPStan](https://github.com/ericsizemore/pagination/actions/workflows/main.yml/badge.svg)](https://github.com/ericsizemore/pagination/actions/workflows/main.yml)

[![Latest Stable Version](https://img.shields.io/packagist/v/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)
[![Downloads per Month](https://img.shields.io/packagist/dm/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)
[![License](https://img.shields.io/packagist/l/esi/pagination.svg)](https://packagist.org/packages/esi/pagination)

Pagination library that implements a paging interface on collections of things.

This library is a fork of [`AshleyDawson\SimplePagination`](https://github.com/AshleyDawson/SimplePagination).

## Installation

You can install Pagination via [Composer](https://getcomposer.org/). To do that, simply `require` the 
package in your `composer.json` file like so:

```json
{
    "require": {
        "esi/pagination": "^2.0"
    }
}
```

Then run `composer update` to install the package.

## How Pagination Works

I've tried to make Pagination as simple, flexible, and easy to use as possible. There are four main elements that
describe the operation of Pagination. These are:

* Paginator service
* Item total callback
* Slice callback
* Pagination model

The **Paginator** service performs the pagination algorithm, generating the page range and item collection slices.
When it's done, it will return a **Pagination** object filled with the item collection slice and metadata.

The two main operations the **Paginator** service will perform on your collection (or data set) are denoted by two
callback methods passed to the **Paginator** service. The first one is the **Item total callback**. This callback is
used to determine the total number of items in your collection (returned as an integer). The second one is the 
**Slice callback**. This callback actually slices your collection given an **offset** and **length** argument.

The idea behind using these callbacks is so that Pagination is kept, well, simple! The real power comes with
the flexibility. You can use Pagination with just about any collection you want. From simple arrays to database
lists to [Doctrine](http://www.doctrine-project.org/) collections to [Solr](http://lucene.apache.org/solr/) result 
sets - we've got you covered! It really doesn't matter what we paginate - as long as it's a collection of things and you 
can count and slice it.

### Basic Usage

Ok, lets go with the most basic example - paginating over an array.

```php
use Esi\Pagination\Paginator;

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
$paginator->setItemTotalCallback(function () use ($items): int {
    return count($items);
});

// Pass our slice callback.
$paginator->setSliceCallback(function (int $offset, int $length) use ($items): array {
    return array_slice($items, $offset, $length);
});

// Paginate the item collection, passing the current page number (e.g. from the current request).
$pagination = $paginator->paginate(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT));

// Ok, from here on is where we'd be inside a template of view (e.g. pass $pagination to your view).

// Iterate over the items on this page.
foreach ($pagination->getItems() as $item) {
    echo $item . '<br />';
}

// Let's build a basic page navigation structure.
foreach ($pagination->getPages() as $page) {
    echo '<a href="?page=' . $page . '">' . $page . '</a> ';
}
```

There are lots of other pieces of meta data held within the [pagination object](#pagination-object). These can be used for building
first, last, previous and next buttons.

### MySQL Example

Let's take the example above and use a MySQL result set instead of an array.

```php
use Esi\Pagination\Paginator;

// Instantiate a new paginator service.
$paginator = new Paginator();

// Set some parameters (optional).
$paginator
    ->setItemsPerPage(10) // Give us a maximum of 10 items per page.
    ->setPagesInRange(5)  // How many pages to display in navigation (e.g. if we have a lot of pages to get through).
;

// Connect to a database.
$mysql = new mysqli('localhost', 'root', 'password', 'myDatabase');

// Pass our item total callback.
$paginator->setItemTotalCallback(function () use($mysql): int {

    // Run count query.
    $result = $mysql->query("SELECT COUNT(*) AS `totalCount` FROM `TestData`");
    $row = $result->fetch_array(MYSQLI_ASSOC);
    
    // Return the count, cast as an integer.
    return (int) $row['totalCount'];
});

// Pass our slice callback.
$paginator->setSliceCallback(function (int $offset, int $length) use ($mysql): array {
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
    echo $item['Name'] . '<br />';
}

// Let's build a basic page navigation structure.
foreach ($pagination->getPages() as $page) {
    echo '<a href="?page=' . $page . '">' . $page . '</a> ';
}
```

**Note:** The example above uses `mysqli` etc. as I tried to make it as simple as possible. In the real world please use [PDO](https://www.php.net/pdo), [Doctrine DBAL](https://www.doctrine-project.org/projects/dbal.html), etc.

It really doesn't matter what sort of collection you return from the Paginator::setSliceCallback() callback. It will always end up in Pagination::getItems().

### Constructor Configuration

You can also configure the paginator with a configuration array passed to the constructor. For example:

```php
$paginator = new Paginator([
    'itemTotalCallback' => function () {
        // ...
    },
    'sliceCallback' => function (int $offset, int $length) {
        // ...
    },
    'itemsPerPage' => 10,
    'pagesInRange' => 5,
]);
```

### Pagination as an Iterator

The `Pagination` object returned from the `Paginator` service implements `\IteratorAggregate` and `\Countable` so you can do things like this in your view:

```php
if (count($pagination) > 0) {
    foreach ($pagination as $item) {
        echo $item . '<br />';
    }
}
```

### Arbitrary Pagination Metadata

During both item total and slice callbacks you have the option of passing arbitrary metadata to the pagination object. This is an optional feature
and is useful if you have a use-case where additional data is returned by these operations and you want to access it from the pagination object whilst listing
the items. A good example of this is when using search engines such as [ElasticSearch](https://www.elastic.co/elasticsearch), you can pass back secondary information - like aggregations, etc. A generic example can be seen below:

```php

use Esi\Pagination\Pagination;

// ...

$paginator->setItemTotalCallback(function (Pagination $pagination) use ($items): int {
    // Pass arbitrary metadata to pagination object.
    $pagination->setMeta(['my', 'meta', 'data']);
    
    return count($items);
});

$paginator->setSliceCallback(function (int $offset, int $length, Pagination $pagination) use ($items): array {
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

### Pre and Post Query Callbacks

Before and after the count and slice queries, you can set callbacks to fire. To set them, do the following:

```php
$paginator->setBeforeQueryCallback(function (Paginator $paginator, Pagination $pagination) {

});

$paginator->setAfterQueryCallback(function (Paginator $paginator, Pagination $pagination) {

});
```

This is handy if you want to perform some function before and after each query is made.

### Pagination Object

The result of the `Paginator::paginate()` operation is to produce a `Pagination` model object, which carries the item collection for 
the current page plus the meta information for the collection, e.g. pages array, next page number, previous page number, etc.

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
echo '<a href="?page=' . $pagination->getFirstPageNumber() . '">First Page</a> ';

// Render the previous page link (note: the previous page number could be null),
echo '<a href="?page=' . $pagination->getPreviousPageNumber() . '">Previous Page</a> ';

// Render page range links,
foreach ($pagination->getPages() as $page) {
    echo '<a href="?page=' . $page . '">' . $page . '</a> ';
}

// Render the next page link (note: the next page number could be null),
echo '<a href="?page=' . $pagination->getNextPageNumber() . '">Next Page</a> ';

// Render the last page link,
echo '<a href="?page=' . $pagination->getLastPageNumber() . '">Last Page</a>';
```

## About

### Requirements

- Pagination works with PHP 8.2.0 or above.

### Submitting bugs and feature requests

Bugs and feature requests are tracked on [GitHub](https://github.com/ericsizemore/pagination/issues)

Issues are the quickest way to report a bug. If you find a bug or documentation error, please check the following first:

* That there is not an Issue already open concerning the bug
* That the issue has not already been addressed (within closed Issues, for example)

### Contributing

Pagination accepts contributions of code and documentation from the community. 
These contributions can be made in the form of Issues or [Pull Requests](http://help.github.com/send-pull-requests/) on the [Pagination repository](https://github.com/ericsizemore/pagination).

Pagination is licensed under the MIT license. When submitting new features or patches to Pagination, you are giving permission to license those features or patches under the MIT license.

Pagination tries to adhere to PHPStan level 9 with strict rules and bleeding edge. Please ensure any contributions do as well.

#### Guidelines

Before we look into how, here are the guidelines. If your Pull Requests fail to pass these guidelines it will be declined and you will need to re-submit when you’ve made the changes. This might sound a bit tough, but it is required for me to maintain quality of the code-base.

#### PHP Style

Please ensure all new contributions match the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style guide. The project is not fully PSR-12 compatible, yet; however, to ensure the easiest transition to the coding guidelines, I would like to go ahead and request that any contributions follow them.

#### Documentation

If you change anything that requires a change to documentation then you will need to add it. New methods, parameters, changing default values, adding constants, etc are all things that will require a change to documentation. The change-log must also be updated for every change. Also PHPDoc blocks must be maintained.

##### Documenting functions/variables (PHPDoc)

Please ensure all new contributions adhere to:

* [PSR-5 PHPDoc](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc.md)
* [PSR-19 PHPDoc Tags](https://github.com/php-fig/fig-standards/blob/master/proposed/phpdoc-tags.md)

when documenting new functions, or changing existing documentation.

#### Branching

One thing at a time: A pull request should only contain one change. That does not mean only one commit, but one change - however many commits it took. The reason for this is that if you change X and Y but send a pull request for both at the same time, we might really want X but disagree with Y, meaning we cannot merge the request. Using the Git-Flow branching model you can create new branches for both of these features and send two requests.

### Author

Eric Sizemore - <admin@secondversion.com> - <https://www.secondversion.com>

### License

Pagination is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details


### Acknowledgements

This library is a `fork` of the `AshleyDawson\SimplePagination`(https://github.com/AshleyDawson/SimplePagination) library by `Ashley Dawson`(https://github.com/AshleyDawson).

To see a list of changes in this library in comparison to the original library, please see the [CHANGELOG.md](CHANGELOG.md) file.