<?php

declare(strict_types=1);

/**
 * This file is part of Esi\Pagination.
 *
 * Copyright (C) Eric Sizemore <https://www.secondversion.com/>.
 * Copyright (c) Ashley Dawson <ashley@ashleydawson.co.uk>.
 *
 * This source file is subject to the MIT license. For the full copyright,
 * license information, and credits/acknowledgements, please view the LICENSE
 * and README files that were distributed with this source code.
 */
/**
 * Esi\Pagination is a fork of AshleyDawson\SimplePagination (https://github.com/AshleyDawson/SimplePagination) which is:
 *     Copyright (c) 2015-2019 Ashley Dawson
 *
 * For a list of changes made in Esi\Pagination in comparison to the original library {@see CHANGELOG.md}.
 */

namespace Esi\Pagination;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Pagination class.
 *
 * @implements IteratorAggregate<(int|string), int>
 * @psalm-api
 */
class Pagination implements IteratorAggregate, Countable
{
    /**
     * Current page number of item collection.
     */
    private int $currentPageNumber = 0;

    /**
     * First page number of item collection.
     */
    private int $firstPageNumber = 0;

    /**
     * Given a page range, first page number.
     */
    private int $firstPageNumberInRange = 0;

    /**
     * Item collection.
     *
     * @var array<int>
     */
    private array $items = [];

    /**
     * Currently set amount of items we want per page.
     */
    private int $itemsPerPage = 10;

    /**
     * Last page number of item collection.
     */
    private int $lastPageNumber = 0;

    /**
     * Given a page range, last page number.
     */
    private int $lastPageNumberInRange = 0;

    /**
     * Optional meta data to include with pagination.
     *
     * @var array<int|string, string>|array<mixed>
     */
    private array $meta = [];

    /**
     * Next page number. Will be null if on the last page.
     */
    private ?int $nextPageNumber = null;

    /**
     * Page collection.
     *
     * @var array<int>
     */
    private array $pages = [];

    /**
     * Previous page number. Will be null if on the first page.
     */
    private ?int $previousPageNumber = null;

    /**
     * Total number of items in the collection.
     */
    private int $totalNumberOfItems = 0;

    /**
     * Total number of pages for the item collection.
     */
    private int $totalNumberOfPages = 0;

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return \count($this->items);
    }

    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    public function getFirstPageNumber(): int
    {
        return $this->firstPageNumber;
    }

    public function getFirstPageNumberInRange(): int
    {
        return $this->firstPageNumberInRange;
    }

    /**
     * Returns the current collection.
     *
     * @return array<int>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @inheritDoc
     *
     * @return ArrayIterator<(int|string), int>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function getLastPageNumber(): int
    {
        return $this->lastPageNumber;
    }

    public function getLastPageNumberInRange(): int
    {
        return $this->lastPageNumberInRange;
    }

    /**
     * @return array<int|string, string>|array<mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getNextPageNumber(): ?int
    {
        return $this->nextPageNumber;
    }

    /**
     * @return array<int>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    public function getPreviousPageNumber(): ?int
    {
        return $this->previousPageNumber;
    }

    public function getTotalNumberOfItems(): int
    {
        return $this->totalNumberOfItems;
    }

    public function getTotalNumberOfPages(): int
    {
        return $this->totalNumberOfPages;
    }

    public function setCurrentPageNumber(int $currentPageNumber): static
    {
        $this->currentPageNumber = $currentPageNumber;

        return $this;
    }

    public function setFirstPageNumber(int $firstPageNumber): static
    {
        $this->firstPageNumber = $firstPageNumber;

        return $this;
    }

    public function setFirstPageNumberInRange(int $firstPageNumberInRange): static
    {
        $this->firstPageNumberInRange = $firstPageNumberInRange;

        return $this;
    }

    /**
     * Sets the item collection to be paginated.
     *
     * @param array<int> $items
     */
    public function setItems(array $items): static
    {
        $this->items = $items;

        return $this;
    }

    public function setItemsPerPage(int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function setLastPageNumber(int $lastPageNumber): static
    {
        $this->lastPageNumber = $lastPageNumber;

        return $this;
    }

    public function setLastPageNumberInRange(int $lastPageNumberInRange): static
    {
        $this->lastPageNumberInRange = $lastPageNumberInRange;

        return $this;
    }

    /**
     * @param array<int|string, string>|array<mixed> $meta
     */
    public function setMeta(array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }

    public function setNextPageNumber(?int $nextPageNumber): static
    {
        $this->nextPageNumber = $nextPageNumber;

        return $this;
    }

    /**
     * @param array<int> $pages
     */
    public function setPages(array $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function setPreviousPageNumber(?int $previousPageNumber): static
    {
        $this->previousPageNumber = $previousPageNumber;

        return $this;
    }

    public function setTotalNumberOfItems(int $totalNumberOfItems): static
    {
        $this->totalNumberOfItems = $totalNumberOfItems;

        return $this;
    }

    public function setTotalNumberOfPages(int $totalNumberOfPages): static
    {
        $this->totalNumberOfPages = $totalNumberOfPages;

        return $this;
    }
}
