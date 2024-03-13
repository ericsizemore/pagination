<?php

declare(strict_types=1);

/**
 * Pagination - Simple, lightweight and universal service that implements pagination on collections of things.
 *
 * @author    Eric Sizemore <admin@secondversion.com>
 * @version   2.0.2
 * @copyright (C) 2024 Eric Sizemore
 * @license   The MIT License (MIT)
 *
 * Copyright (C) 2024 Eric Sizemore <https://www.secondversion.com/>.
 * Copyright (c) 2015-2019 Ashley Dawson <ashley@ashleydawson.co.uk>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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

use function count;

/**
 * Pagination class.
 *
 * @implements IteratorAggregate<(int|string), int>
 */
class Pagination implements IteratorAggregate, Countable
{
    /**
     * Item collection.
     *
     * @var array<int>
     */
    private array $items = [];

    /**
     * Page collection.
     *
     * @var array<int>
     */
    private array $pages = [];

    /**
     * Total number of pages for the item collection.
     */
    private int $totalNumberOfPages;

    /**
     * Current page number of item collection.
     */
    private int $currentPageNumber;

    /**
     * First page number of item collection.
     */
    private int $firstPageNumber;

    /**
     * Last page number of item collection.
     */
    private int $lastPageNumber;

    /**
     * Previous page number. Will be null if on the first page.
     */
    private ?int $previousPageNumber = null;

    /**
     * Next page number. Will be null if on the last page.
     */
    private ?int $nextPageNumber = null;

    /**
     * Currently set amount of items we want per page.
     */
    private int $itemsPerPage;

    /**
     * Total number of items in the collection.
     */
    private int $totalNumberOfItems;

    /**
     * Given a page range, first page number.
     */
    private int $firstPageNumberInRange;

    /**
     * Given a page range, last page number.
     */
    private int $lastPageNumberInRange;

    /**
     * Optional meta data to include with pagination.
     *
     * @var array<int|string, string>|array<mixed>
     */
    private array $meta;

    /**
     * Returns the current collection.
     *
     * @return array<int>
     */
    public function getItems(): array
    {
        return $this->items;
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

    public function getCurrentPageNumber(): int
    {
        return $this->currentPageNumber;
    }

    public function setCurrentPageNumber(int $currentPageNumber): static
    {
        $this->currentPageNumber = $currentPageNumber;

        return $this;
    }

    public function getFirstPageNumber(): int
    {
        return $this->firstPageNumber;
    }

    public function setFirstPageNumber(int $firstPageNumber): static
    {
        $this->firstPageNumber = $firstPageNumber;

        return $this;
    }

    public function getFirstPageNumberInRange(): int
    {
        return $this->firstPageNumberInRange;
    }

    public function setFirstPageNumberInRange(int $firstPageNumberInRange): static
    {
        $this->firstPageNumberInRange = $firstPageNumberInRange;

        return $this;
    }

    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    public function setItemsPerPage(int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function getLastPageNumber(): int
    {
        return $this->lastPageNumber;
    }

    public function setLastPageNumber(int $lastPageNumber): static
    {
        $this->lastPageNumber = $lastPageNumber;

        return $this;
    }

    public function getLastPageNumberInRange(): int
    {
        return $this->lastPageNumberInRange;
    }

    public function setLastPageNumberInRange(int $lastPageNumberInRange): static
    {
        $this->lastPageNumberInRange = $lastPageNumberInRange;

        return $this;
    }

    public function getNextPageNumber(): ?int
    {
        return $this->nextPageNumber;
    }

    public function setNextPageNumber(?int $nextPageNumber): static
    {
        $this->nextPageNumber = $nextPageNumber;

        return $this;
    }

    /**
     * @return array<int>
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param array<int> $pages
     */
    public function setPages(array $pages): static
    {
        $this->pages = $pages;

        return $this;
    }

    public function getPreviousPageNumber(): ?int
    {
        return $this->previousPageNumber;
    }

    public function setPreviousPageNumber(?int $previousPageNumber): static
    {
        $this->previousPageNumber = $previousPageNumber;

        return $this;
    }

    public function getTotalNumberOfItems(): int
    {
        return $this->totalNumberOfItems;
    }

    public function setTotalNumberOfItems(int $totalNumberOfItems): static
    {
        $this->totalNumberOfItems = $totalNumberOfItems;

        return $this;
    }

    public function getTotalNumberOfPages(): int
    {
        return $this->totalNumberOfPages;
    }

    public function setTotalNumberOfPages(int $totalNumberOfPages): static
    {
        $this->totalNumberOfPages = $totalNumberOfPages;

        return $this;
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

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return array<int|string, string>|array<mixed>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array<int|string, string>|array<mixed> $meta
     */
    public function setMeta(array $meta): static
    {
        $this->meta = $meta;

        return $this;
    }
}
