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
