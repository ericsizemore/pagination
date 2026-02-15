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

use function array_filter;
use function ceil;
use function iterator_to_array;
use function max;
use function min;
use function range;

use const ARRAY_FILTER_USE_BOTH;

/**
 * Main Paginator Class.
 *
 * @psalm-import-type ItemTotalCallback from PaginatorInterface
 * @psalm-import-type SliceCallback from PaginatorInterface
 * @psalm-import-type BeforeAfterQueryCallback from PaginatorInterface
 *
 * @see Tests\PaginatorTest
 */
class Paginator implements PaginatorInterface
{
    /**
     * A callback to run after the count and slice queries.
     *
     * @var null|BeforeAfterQueryCallback
     */
    private ?Closure $afterQueryCallback = null;

    /**
     * A callback to run before the count and slice queries.
     *
     * @var null|BeforeAfterQueryCallback
     */
    private ?Closure $beforeQueryCallback = null;

    /**
     * Number of items to include per page.
     *
     * @var int<-1, max>
     */
    private int $itemsPerPage = 10;

    /**
     * A callback that is used to determine the total number of items in your collection (returned as an integer).
     *
     * @var null|ItemTotalCallback
     */
    private ?Closure $itemTotalCallback = null;

    /**
     * Number of pages in range.
     *
     * @var int<0, max>
     */
    private int $pagesInRange = 5;

    /**
     * A callback to slice your collection given an offset and length argument.
     *
     * @var ?SliceCallback
     */
    private ?Closure $sliceCallback = null;

    /**
     * Constructor - passing optional configuration.
     *
     * ```
     * $paginator = new Paginator([
     *     'itemTotalCallback' => static function (): int {
     *         // ...
     *     },
     *     'sliceCallback' => static function (int $offset, int $length, ?Pagination $pagination = null): array {
     *         // ...
     *     },
     *     'itemsPerPage' => 10,
     *     'pagesInRange' => 5,
     * ]);
     * ```
     *
     * @param null|array{
     *     itemTotalCallback?: ItemTotalCallback,
     *     sliceCallback?: SliceCallback,
     *     itemsPerPage?: int<0, max>,
     *     pagesInRange?: int<0, max>
     * }|array{} $config
     */
    public function __construct(?array $config = null)
    {
        $config = self::validateConfig($config);

        if ($config === []) {
            return;
        }

        $this->setItemTotalCallback($config['itemTotalCallback'] ?? static fn (): int => 0);
        $this->setSliceCallback($config['sliceCallback'] ?? static fn (int $limit, int $offset): array => [$limit, $offset]);
        $this->setItemsPerPage($config['itemsPerPage'] ?? 10);
        $this->setPagesInRange($config['pagesInRange'] ?? 5);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAfterQueryCallback(): ?Closure
    {
        return $this->afterQueryCallback;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getBeforeQueryCallback(): ?Closure
    {
        return $this->beforeQueryCallback;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getItemsPerPage(): int
    {
        return $this->itemsPerPage;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getItemTotalCallback(): ?Closure
    {
        return $this->itemTotalCallback;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getPagesInRange(): int
    {
        return $this->pagesInRange;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getSliceCallback(): ?Closure
    {
        return $this->sliceCallback;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function paginate(int $currentPageNumber = 1): Pagination
    {
        if (!$this->itemTotalCallback instanceof Closure) {
            throw new CallbackNotFoundException(
                'Item total callback not found, set it using Paginator::setItemTotalCallback()'
            );
        }

        if (!$this->sliceCallback instanceof Closure) {
            throw new CallbackNotFoundException(
                'Slice callback not found, set it using Paginator::setSliceCallback()'
            );
        }

        if ($currentPageNumber <= 0) {
            throw new InvalidPageNumberException(
                \sprintf('Current page number must have a value of 1 or more, %s given', $currentPageNumber)
            );
        }

        $sliceCallback       = $this->sliceCallback;
        $itemTotalCallback   = $this->itemTotalCallback;
        $beforeQueryCallback = $this->prepareBeforeQueryCallback();
        $afterQueryCallback  = $this->prepareAfterQueryCallback();

        $pagination = new Pagination();

        $beforeQueryCallback($this, $pagination);
        $totalNumberOfItems = $itemTotalCallback($pagination);
        $afterQueryCallback($this, $pagination);

        /**
         * @var int<0, max> $numberOfPages
         */
        $numberOfPages = (int) ceil($totalNumberOfItems / $this->itemsPerPage);
        $pagesInRange  = min($this->pagesInRange, $numberOfPages);
        $pages         = self::determinePageRange($currentPageNumber, $pagesInRange, $numberOfPages);

        /**
         * @var int<0, max> $offset
         */
        $offset = ($currentPageNumber - 1) * $this->itemsPerPage;

        $beforeQueryCallback($this, $pagination);

        if (-1 === $this->itemsPerPage) {
            /**
             * @psalm-var array<array-key, int<0, max>>|Iterator<array-key, int<0, max>> $items
             */
            $items = $sliceCallback(0, 999_999_999, $pagination);
        } else {
            /**
             * @psalm-var array<array-key, int<-1, max>>|Iterator<array-key, int<-1, max>> $items
             */
            $items = $sliceCallback($offset, $this->itemsPerPage, $pagination);
        }

        if ($items instanceof Iterator) {
            /**
             * @psalm-var array<array-key, int<0, max>> $items
             */
            $items = iterator_to_array($items);
        }

        $afterQueryCallback($this, $pagination);

        $previousPageNumber = self::determinePreviousPageNumber($currentPageNumber);
        $nextPageNumber     = self::determineNextPageNumber($currentPageNumber, $numberOfPages);

        /** @var non-empty-array<int<0, max>> $pages * */
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
     * @inheritDoc
     */
    #[\Override]
    public function setAfterQueryCallback(?Closure $afterQueryCallback): static
    {
        $this->afterQueryCallback = $afterQueryCallback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function setBeforeQueryCallback(?Closure $beforeQueryCallback): static
    {
        $this->beforeQueryCallback = $beforeQueryCallback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function setItemsPerPage(int $itemsPerPage): static
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function setItemTotalCallback(?Closure $itemTotalCallback): static
    {
        $this->itemTotalCallback = $itemTotalCallback;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function setPagesInRange(int $pagesInRange): static
    {
        $this->pagesInRange = $pagesInRange;

        return $this;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function setSliceCallback(?Closure $sliceCallback): static
    {
        $this->sliceCallback = $sliceCallback;

        return $this;
    }

    /**
     * A helper function to {@see self::paginate()}.
     *
     * Ensures the afterQueryCallback is a valid Closure. If the currently set
     * afterQueryCallback is null, it will return an empty Closure object.
     */
    protected function prepareAfterQueryCallback(): Closure
    {
        if ($this->afterQueryCallback instanceof Closure) {
            return $this->afterQueryCallback;
        }

        return static function (): void {};
    }

    /**
     * A helper function to {@see self::paginate()}.
     *
     * Ensures the beforeQueryCallback is a valid Closure. If the currently set
     * beforeQueryCallback is null, it will return an empty Closure object.
     */
    protected function prepareBeforeQueryCallback(): Closure
    {
        if ($this->beforeQueryCallback instanceof Closure) {
            return $this->beforeQueryCallback;
        }

        return static function (): void {};
    }

    /**
     * A helper function to {@see self::paginate()}.
     *
     * Determines the next page number based on the current page number.
     */
    protected static function determineNextPageNumber(int $currentPageNumber, int $numberOfPages): ?int
    {
        if (($currentPageNumber + 1) <= $numberOfPages) {
            return $currentPageNumber + 1;
        }

        return null;
    }

    /**
     * A helper function to {@see self::paginate()}.
     *
     * Determines the number of pages in range given the current page number, currently
     * set pages in range, and total number of pages.
     *
     * @return non-empty-list<int<0, max>>
     */
    protected static function determinePageRange(int $currentPageNumber, int $pagesInRange, int $numberOfPages): array
    {
        $change = (int) ceil($pagesInRange / 2);

        if (($currentPageNumber - $change) > ($numberOfPages - $pagesInRange)) {
            /**
             * @var non-empty-list<int<0, max>>
             */
            return range(($numberOfPages - $pagesInRange) + 1, $numberOfPages);
        }

        if (($currentPageNumber - $change) < 0) {
            $change = $currentPageNumber;
        }

        $offset = $currentPageNumber - $change;

        /**
         * @var non-empty-list<int<0, max>>
         */
        return range(($offset + 1), $offset + $pagesInRange);
    }

    /**
     * A helper function to {@see self::paginate()}.
     *
     * Determines the previous page number based on the current page number.
     */
    protected static function determinePreviousPageNumber(int $currentPageNumber): ?int
    {
        if (($currentPageNumber - 1) > 0) {
            return $currentPageNumber - 1;
        }

        return null;
    }

    /**
     * Helper function for __construct() to validate the passed $config.
     *
     * @param null|array{
     *     itemTotalCallback?: ItemTotalCallback,
     *     sliceCallback?: SliceCallback,
     *     itemsPerPage?: int<0, max>,
     *     pagesInRange?: int<0, max>
     * }|array{} $config Expected array signature.
     *
     * @return array{
     *     itemTotalCallback?: ItemTotalCallback,
     *     sliceCallback?: SliceCallback,
     *     itemsPerPage?: int<0, max>,
     *     pagesInRange?: int<0, max>
     * }|array{}
     */
    protected static function validateConfig(?array $config = null): array
    {
        /**
         * @var null|array<string> $validKeys
         */
        static $validKeys;

        $validKeys ??= ['itemTotalCallback', 'sliceCallback', 'itemsPerPage', 'pagesInRange'];

        $config ??= [];

        /**
         * @var array{
         *     itemTotalCallback?: ItemTotalCallback,
         *     sliceCallback?: SliceCallback,
         *     itemsPerPage?: int<0, max>,
         *     pagesInRange?: int<0, max>
         * }|array{} $filtered
         */
        $filtered = array_filter($config, static function (mixed $value, string $key) use ($validKeys): bool {
            if (!\in_array($key, $validKeys, true)) {
                return false;
            }

            return match ($key) {
                'itemTotalCallback', 'sliceCallback' => $value instanceof Closure,
                default => \is_int($value)
            };
        }, ARRAY_FILTER_USE_BOTH);

        return $filtered;
    }
}
