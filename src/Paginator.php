<?php

declare(strict_types=1);

/**
 * Pagination - Simple, lightweight and universal service that implements pagination on collections of things.
 *
 * @author    Eric Sizemore <admin@secondversion.com>
 * @version   2.0.0
 * @copyright (C) 2024 Eric Sizemore
 * @license   The MIT License (MIT)
 *
 * Copyright (C) 2024 Eric Sizemore<https://www.secondversion.com/>.
 * Copyright (c) 2015-2019 Ashley Dawson<ashley@ashleydawson.co.uk>
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
use Iterator;

use function ceil;
use function iterator_to_array;
use function max;
use function min;
use function range;
use function sprintf;

/**
 * Class Paginator
 * @see \Esi\Pagination\Tests\PaginatorTest
 */
class Paginator implements PaginatorInterface
{
    /**
     * A callback that is used to determine the total number of items in your collection (returned as an integer).
     */
    private ?Closure $itemTotalCallback   = null;

    /**
     * A callback to slice your collection given an offset and length argument.
     */
    private ?Closure $sliceCallback       = null;

    /**
     * A callback to run before the count and slice queries.
     */
    private ?Closure $beforeQueryCallback = null;

    /**
     * A callback to run after the count and slice queries.
     */
    private ?Closure $afterQueryCallback  = null;

    /**
     * Number of items to include per page.
     */
    private int $itemsPerPage = 10;

    /**
     */
    private int $pagesInRange = 5;

    /**
     * Constructor - passing optional configuration
     *
     * <code>
     * $paginator = new Paginator(array(
     *     'itemTotalCallback' => function () {
     *         // ...
     *     },
     *     'sliceCallback' => function (int $offset, int $length) {
     *         // ...
     *     },
     *     'itemsPerPage' => 10,
     *     'pagesInRange' => 5
     * ));
     * </code>
     *
     * @param null|array{}|array{
     *     itemTotalCallback: Closure,
     *     sliceCallback: Closure,
     *     itemsPerPage: int,
     *     pagesInRange: int
     * } $config
     */
    public function __construct(?array $config = null)
    {
        if ($config !== null && $config !== []) {
            $this->setItemTotalCallback($config['itemTotalCallback']);
            $this->setSliceCallback($config['sliceCallback']);
            $this->setItemsPerPage($config['itemsPerPage']);
            $this->setPagesInRange($config['pagesInRange']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $currentPageNumber = 1): Pagination
    {
        if ($this->itemTotalCallback === null) {
            throw new CallbackNotFoundException(
                'Item total callback not found, set it using Paginator::setItemTotalCallback()'
            );
        }

        if ($this->sliceCallback === null) {
            throw new CallbackNotFoundException(
                'Slice callback not found, set it using Paginator::setSliceCallback()'
            );
        }

        if ($currentPageNumber <= 0) {
            throw new InvalidPageNumberException(
                sprintf('Current page number must have a value of 1 or more, %s given', $currentPageNumber)
            );
        }

        $sliceCallback       = $this->sliceCallback;
        $itemTotalCallback   = $this->itemTotalCallback;
        /** @var Closure $beforeQueryCallback */
        $beforeQueryCallback = $this->prepareBeforeQueryCallback();
        /** @var Closure $afterQueryCallback  */
        $afterQueryCallback  = $this->prepareAfterQueryCallback();

        $pagination = new Pagination();

        $beforeQueryCallback($this, $pagination);
        $totalNumberOfItems = (int) $itemTotalCallback($pagination);
        $afterQueryCallback($this, $pagination);

        $numberOfPages = (int) ceil($totalNumberOfItems / $this->itemsPerPage);
        $pagesInRange  = $this->pagesInRange > $numberOfPages ? $numberOfPages : $this->pagesInRange;
        $pages         = $this->determinePageRange($currentPageNumber, $pagesInRange, $numberOfPages);
        $offset        = ($currentPageNumber - 1) * $this->itemsPerPage;

        $beforeQueryCallback($this, $pagination);

        if (-1 === $this->itemsPerPage) {
            $items = $sliceCallback(0, 999999999, $pagination);
        } else {
            $items = $sliceCallback($offset, $this->itemsPerPage, $pagination);
        }

        if ($items instanceof Iterator) {
            $items = iterator_to_array($items);
        }

        $afterQueryCallback($this, $pagination);

        $previousPageNumber = $this->determinePreviousPageNumber($currentPageNumber);
        $nextPageNumber     = $this->determineNextPageNumber($currentPageNumber, $numberOfPages);

        /** @var non-empty-array<int> $pages **/
        $pagination
            ->setItems($items)
            ->setPages($pages)
            ->setTotalNumberOfPages($numberOfPages)
            ->setCurrentPageNumber($currentPageNumber)
            ->setFirstPageNumber(1)
            ->setLastPageNumber($numberOfPages)
            ->setPreviousPageNumber($previousPageNumber)
            ->setNextPageNumber($nextPageNumber)
            ->setItemsPerPage($this->itemsPerPage)
            ->setTotalNumberOfItems($totalNumberOfItems)
            ->setFirstPageNumberInRange(min($pages))
            ->setLastPageNumberInRange(max($pages))
        ;

        return $pagination;
    }

    /**
     * {@inheritdoc}
     */
    public function getSliceCallback(): ?Closure
    {
        return $this->sliceCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function setSliceCallback(?Closure $sliceCallback): static
    {
        $this->sliceCallback = $sliceCallback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemTotalCallback(): ?Closure
    {
        return $this->itemTotalCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getBeforeQueryCallback(): ?Closure
    {
        return $this->beforeQueryCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function setBeforeQueryCallback(?Closure $beforeQueryCallback): static
    {
        $this->beforeQueryCallback = $beforeQueryCallback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAfterQueryCallback(): ?Closure
    {
        return $this->afterQueryCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function setAfterQueryCallback(?Closure $afterQueryCallback): static
    {
        $this->afterQueryCallback = $afterQueryCallback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setItemTotalCallback(?Closure $itemTotalCallback): static
    {
        $this->itemTotalCallback = $itemTotalCallback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * {@inheritdoc}
     */
    public function setItemsPerPage(int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPagesInRange(): int
    {
        return $this->pagesInRange;
    }

    /**
     * {@inheritdoc}
     */
    public function setPagesInRange(int $pagesInRange): static
    {
        $this->pagesInRange = $pagesInRange;

        return $this;
    }

    // Helper functions to the main paginate() function.

    /**
     */
    protected function prepareBeforeQueryCallback(): Closure
    {
        if ($this->beforeQueryCallback instanceof Closure) {
            return $this->beforeQueryCallback;
        }

        return static function (): void {};
    }

    /**
     */
    protected function prepareAfterQueryCallback(): Closure
    {
        if ($this->afterQueryCallback instanceof Closure) {
            return $this->afterQueryCallback;
        }

        return static function (): void {};
    }

    /**
     * @return array<int>
     */
    protected function determinePageRange(int $currentPageNumber, int $pagesInRange, int $numberOfPages): array
    {
        $change = (int) ceil($pagesInRange / 2);

        if (($currentPageNumber - $change) > ($numberOfPages - $pagesInRange)) {
            $pages = range(($numberOfPages - $pagesInRange) + 1, $numberOfPages);
        } else {
            if (($currentPageNumber - $change) < 0) {
                $change = $currentPageNumber;
            }

            $offset = $currentPageNumber - $change;
            $pages  = range(($offset + 1), $offset + $pagesInRange);
        }

        return $pages;
    }

    /**
     */
    protected function determinePreviousPageNumber(int $currentPageNumber): ?int
    {
        $previousPageNumber = null;

        if (($currentPageNumber - 1) > 0) {
            $previousPageNumber = $currentPageNumber - 1;
        }

        return $previousPageNumber;
    }

    /**
     */
    protected function determineNextPageNumber(int $currentPageNumber, int $numberOfPages): ?int
    {
        $nextPageNumber = null;

        if (($currentPageNumber + 1) <= $numberOfPages) {
            $nextPageNumber = $currentPageNumber + 1;
        }

        return $nextPageNumber;
    }
}
