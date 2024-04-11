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

use Closure;
use Esi\Pagination\Exception\CallbackNotFoundException;
use Esi\Pagination\Exception\InvalidPageNumberException;

/**
 * Interface PaginatorInterface.
 */
interface PaginatorInterface
{
    /**
     * Returns the currently assigned after query callback, null if not set.
     */
    public function getAfterQueryCallback(): ?Closure;

    /**
     * Returns the currently assigned before query callback, null if not set.
     */
    public function getBeforeQueryCallback(): ?Closure;

    /**
     * Returns the number of items per page.
     */
    public function getItemsPerPage(): int;

    /**
     * Returns the currently assigned item total callback, or null if not set.
     */
    public function getItemTotalCallback(): ?Closure;

    /**
     * Returns the number of pages in range.
     */
    public function getPagesInRange(): int;

    /**
     * Returns the currently assigned slice callback, or null if not set.
     */
    public function getSliceCallback(): ?Closure;

    /**
     * Run paginate algorithm using the current page number.
     *
     * @param int $currentPageNumber Page number, usually passed from the current request.
     *
     * @return Pagination Collection of items returned by the slice callback with pagination meta information.
     *
     * @throws CallbackNotFoundException
     * @throws InvalidPageNumberException
     */
    public function paginate(int $currentPageNumber = 1): Pagination;

    /**
     * Sets the after query callback. Called after the count and slice queries.
     *
     * This should be a Closure, and there is no real return or signature expectation.
     */
    public function setAfterQueryCallback(?Closure $afterQueryCallback): PaginatorInterface;

    /**
     * Sets the before query callback. Called before the count and slice queries.
     *
     * This should be a Closure, and there is no real return or signature expectation.
     */
    public function setBeforeQueryCallback(?Closure $beforeQueryCallback): PaginatorInterface;

    /**
     * Sets the number of items per page.
     */
    public function setItemsPerPage(int $itemsPerPage): PaginatorInterface;

    /**
     * Sets the item total callback. Used to determine the total number of items in your collection.
     *
     * This should be a Closure, and it would be expected to return an integer. For example:
     *
     * function() use($items): int {
     *     return count($items);
     * }
     */
    public function setItemTotalCallback(?Closure $itemTotalCallback): PaginatorInterface;

    /**
     * Sets the number of pages in range.
     */
    public function setPagesInRange(int $pagesInRange): PaginatorInterface;

    /**
     * Sets the slice callback. Actually slices your collection given an **offset** and **length** argument.
     *
     * This should be a Closure, and it would be expected to return an array. For example:
     *
     * function (int $offset, int $length) use ($items): array {
     *     return array_slice($items, $offset, $length);
     * }
     */
    public function setSliceCallback(?Closure $sliceCallback): PaginatorInterface;
}
