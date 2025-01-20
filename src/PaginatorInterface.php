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
use Iterator;

/**
 * Interface PaginatorInterface.
 *
 * These Closure signatures are gnarly, so further work will be/needs to be done.
 *
 * @psalm-type ItemTotalCallback = Closure(Pagination): (int<0, max>|int<1, max>)|Closure(): (int<0, max>|int<1, max>)
 * @psalm-type SliceCallback = Closure(int<0, max>, int<0, max>, Pagination): (array<mixed>|list<mixed>|Iterator<mixed, mixed>)|Closure(int<0, max>, int<0, max>, Pagination=): (array<mixed>|list<mixed>|Iterator<mixed, mixed>)
 * @psalm-type BeforeAfterQueryCallback = Closure(Paginator, Pagination): void
 */
interface PaginatorInterface
{
    /**
     * Returns the currently assigned after query callback, null if not set.
     *
     * @return null|BeforeAfterQueryCallback
     */
    public function getAfterQueryCallback(): ?Closure;

    /**
     * Returns the currently assigned before query callback, null if not set.
     *
     * @return null|BeforeAfterQueryCallback
     */
    public function getBeforeQueryCallback(): ?Closure;

    /**
     * Returns the number of items per page.
     *
     * @return int<-1, max>
     */
    public function getItemsPerPage(): int;

    /**
     * Returns the currently assigned item total callback, or null if not set.
     *
     * @return ?ItemTotalCallback
     */
    public function getItemTotalCallback(): ?Closure;

    /**
     * Returns the number of pages in range.
     *
     * @return int<0, max>
     */
    public function getPagesInRange(): int;

    /**
     * Returns the currently assigned slice callback, or null if not set.
     *
     * @return ?SliceCallback
     */
    public function getSliceCallback(): ?Closure;

    /**
     * Run paginate algorithm using the current page number.
     *
     * @param int<0, max> $currentPageNumber Page number, usually passed from the current request.
     *
     * @throws CallbackNotFoundException
     * @throws InvalidPageNumberException
     *
     * @return Pagination Collection of items returned by the slice callback with pagination meta information.
     */
    public function paginate(int $currentPageNumber = 1): Pagination;

    /**
     * Sets the after query callback. Called after the count and slice queries.
     *
     * This should be a Closure that returns `void`.
     *
     * ```
     * static function (Paginator $paginator, Pagination $pagination) use (): void {
     *     // ...
     * }
     * ```
     *
     * @param null|BeforeAfterQueryCallback $afterQueryCallback
     */
    public function setAfterQueryCallback(?Closure $afterQueryCallback): PaginatorInterface;

    /**
     * Sets the before query callback. Called before the count and slice queries.
     *
     * This should be a Closure that returns `void`.
     *
     * ```
     * static function (Paginator $paginator, Pagination $pagination) use (): void {
     *     // ...
     * }
     * ```
     *
     * @param null|BeforeAfterQueryCallback $beforeQueryCallback
     */
    public function setBeforeQueryCallback(?Closure $beforeQueryCallback): PaginatorInterface;

    /**
     * Sets the number of items per page.
     *
     * @param int<-1, max> $itemsPerPage
     */
    public function setItemsPerPage(int $itemsPerPage): PaginatorInterface;

    /**
     * Sets the item total callback. Used to determine the total number of items in your collection.
     *
     * This should be a Closure, and it would be expected to return an integer. For example:
     *
     * ```
     * static function() use($items): int {
     *     return \count($items);
     * }
     * ```
     *
     * You can also pass Pagination, if needed (to set meta data for example):
     *
     * ```
     * static function (Pagination $pagination): int {
     *     $pagination->setMeta(['meta_3']);
     *
     *     return \count($items);
     * }
     * ```
     *
     * @param ?ItemTotalCallback $itemTotalCallback
     */
    public function setItemTotalCallback(?Closure $itemTotalCallback): PaginatorInterface;

    /**
     * Sets the number of pages in range.
     *
     * @param int<0, max> $pagesInRange
     */
    public function setPagesInRange(int $pagesInRange): PaginatorInterface;

    /**
     * Sets the slice callback. Actually slices your collection given an **offset** and **length** argument.
     *
     * This should be a Closure, and it would be expected to return an array. For example:
     *
     * ```
     * static function (int $offset, int $length) use ($items): array {
     *     return \array_slice($items, $offset, $length);
     * }
     * ```
     *
     * You can also pass Pagination, if needed (to set meta data for example):
     *
     * ```
     * static function (int $offset, int $length, Pagination $pagination) use ($items): array {
     *     $pagination->setMeta(array_merge($pagination->getMeta(), ['meta_4']));
     *
     *     return \array_slice($items, $offset, $length);
     * }
     * ```
     *
     * @param ?SliceCallback $sliceCallback
     */
    public function setSliceCallback(?Closure $sliceCallback): PaginatorInterface;
}
