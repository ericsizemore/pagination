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

namespace Esi\Pagination\Tests;

use ArrayIterator;
use Esi\Pagination\Exception\CallbackNotFoundException;
use Esi\Pagination\Exception\InvalidPageNumberException;
use Esi\Pagination\Pagination;
use Esi\Pagination\Paginator;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function range;

/**
 * @internal
 */
#[CoversClass(Paginator::class)]
#[CoversClass(Pagination::class)]
#[UsesClass(CallbackNotFoundException::class)]
#[UsesClass(InvalidPageNumberException::class)]
final class PaginatorTest extends TestCase
{
    /**
     * Paginator object used throughout testing.
     */
    private Paginator $paginator;

    /**
     * PDO_SQLITE object for the database testing.
     */
    private static PDO $pdo;

    /**
     * Creates our Paginator and PDO objects.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->paginator = new Paginator();
        self::$pdo       = new PDO(\sprintf('sqlite:%s/fixtures/factbook.db', __DIR__));
    }

    public function testBeforeAndAfterQueryCallbacks(): void
    {
        $items = range(0, 27);

        $paginator = new Paginator([
            'itemTotalCallback' => static fn (): int => \count($items),
            'sliceCallback'     => static fn (int $offset, int $length): array => \array_slice($items, $offset, $length),
            'itemsPerPage'      => 10,
            'pagesInRange'      => 5,
        ]);

        $beforeQueryFired = false;
        $paginator->setBeforeQueryCallback(static function (Paginator $paginator, Pagination $pagination) use (&$beforeQueryFired): void {
            $beforeQueryFired = true;
            $paginator->setItemsPerPage(20);
            $pagination->setItemsPerPage(20);
        });

        $afterQueryFired = false;
        $paginator->setAfterQueryCallback(static function (Paginator $paginator, Pagination $pagination) use (&$afterQueryFired): void {
            $afterQueryFired = true;
            $paginator->setPagesInRange(10);
            $pagination->setMeta(['test' => true]);
        });

        self::assertFalse($beforeQueryFired);
        self::assertFalse($afterQueryFired);

        $pagination = $paginator->paginate();

        self::assertSame(20, $paginator->getItemsPerPage());
        self::assertSame(20, $pagination->getItemsPerPage());
        self::assertSame(10, $paginator->getPagesInRange());
        self::assertSame(['test' => true], $pagination->getMeta());

        /**
         * @phpstan-ignore staticMethod.impossibleType
         */
        self::assertTrue($beforeQueryFired);
        /**
         * @phpstan-ignore staticMethod.impossibleType
         */
        self::assertTrue($afterQueryFired);

        $beforeCallback = $paginator->getBeforeQueryCallback();
        $afterCallback  = $paginator->getAfterQueryCallback();

        self::assertInstanceOf(\Closure::class, $beforeCallback);
        self::assertInstanceOf(\Closure::class, $afterCallback);
    }

    /**
     * @psalm-suppress UnusedClosureParam
     *
     * Suppressing as we don't really need to test modifing or using the $paginator or $pagination object,
     * we are just testing for an exception being thrown and the callbacks not firing.
     */
    public function testBeforeAndAfterQueryCallbacksZeroPageNumber(): void
    {
        $items = range(0, 27);

        $paginator = new Paginator([
            'itemTotalCallback' => static fn (): int => \count($items),
            'sliceCallback'     => static fn (int $offset, int $length): array => \array_slice($items, $offset, $length),
            'itemsPerPage'      => 10,
            'pagesInRange'      => 5,
        ]);

        $beforeQueryFired = false;
        $paginator->setBeforeQueryCallback(static function (Paginator $paginator, Pagination $pagination) use (&$beforeQueryFired): void {
            $beforeQueryFired = true;
        });

        $afterQueryFired = false;
        $paginator->setAfterQueryCallback(static function (Paginator $paginator, Pagination $pagination) use (&$afterQueryFired): void {
            $afterQueryFired = true;
        });

        self::assertFalse($beforeQueryFired);
        self::assertFalse($afterQueryFired);

        $this->expectException(InvalidPageNumberException::class);
        $paginator->paginate(0);
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testConstructionWithInvalidConfig(): void
    {
        /**
         * @phpstan-ignore argument.type
         */
        $paginator = new Paginator([
            'itemTotalCallback' => '',
            'sliceCallback'     => '',
            'itemsPerPage'      => '',
            'pagesInRange'      => '',
            'nonexistent'       => '',
        ]);

        self::assertNull($paginator->getItemTotalCallback());
        self::assertNull($paginator->getSliceCallback());
        self::assertSame(10, $paginator->getItemsPerPage());
        self::assertSame(5, $paginator->getPagesInRange());
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testItemTotalCallbackNotFound(): void
    {
        $this->paginator->setItemsPerPage(10)->setPagesInRange(5);

        $this->expectException(CallbackNotFoundException::class);
        $this->paginator->setItemTotalCallback(null);
        /**
         * @phpstan-ignore argument.type
         */
        $this->paginator->setSliceCallback(static fn (): string => 'slice_callback');
        $this->paginator->paginate();
    }

    /**
     * Test pagination of database results. This test uses PDO and a SQLite db, though this is perfectly
     * valid for MySQL, etc. as well.
     *
     * Uses factbook.db, which is licensed under the CC0-1.0 (public domain).
     *
     * @see https://github.com/factbook/factbook.sql/releases
     * @see ../fixtures/factbook.db
     */
    public function testPaginateDbResults(): void
    {
        $paginator = new Paginator();
        $paginator
            ->setItemsPerPage(10)
            ->setPagesInRange(5);

        $paginator->setItemTotalCallback(
            /**
             * @return int<0, max>
             */
            static function (): int {
                /** @var PDOStatement $result */
                $result = self::$pdo->query('SELECT COUNT(*) as totalCount FROM facts');
                /** @var string $row */
                $row = $result->fetchColumn();
                /** @var int<0, max> $row */
                $row = (int) $row;

                return $row;
            }
        );

        // Pass our slice callback.
        $paginator->setSliceCallback(static function (int $offset, int $length): array {
            /** @var PDOStatement $result */
            $result     = self::$pdo->query(\sprintf('SELECT name, area FROM facts ORDER BY area DESC LIMIT %d, %d', $offset, $length), PDO::FETCH_ASSOC);
            $collection = [];

            foreach ($result as $row) {
                $collection[] = $row;
            }

            return $collection;
        });

        $pagination = $paginator->paginate();

        self::assertNotEmpty($pagination->getItems());
        self::assertSame(261, $pagination->getTotalNumberOfItems());
        self::assertSame([
            0 => [
                'name' => 'Russia',
                'area' => 17098242,
            ],
            1 => [
                'name' => 'Canada',
                'area' => 9984670,
            ],
            2 => [
                'name' => 'United States',
                'area' => 9826675,
            ],
            3 => [
                'name' => 'China',
                'area' => 9596960,
            ],
            4 => [
                'name' => 'Brazil',
                'area' => 8515770,
            ],
            5 => [
                'name' => 'Australia',
                'area' => 7741220,
            ],
            6 => [
                'name' => 'European Union',
                'area' => 4324782,
            ],
            7 => [
                'name' => 'India',
                'area' => 3287263,
            ],
            8 => [
                'name' => 'Argentina',
                'area' => 2780400,
            ],
            9 => [
                'name' => 'Kazakhstan',
                'area' => 2724900,
            ],
        ], $pagination->getItems());

        self::assertSame([
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
        ], $pagination->getPages());

        // Paginate the item collection, passing the current page number (e.g. from the current request).
        $pagination = $paginator->paginate(3);

        self::assertNotEmpty($pagination->getItems());
        self::assertSame([
            0 => [
                'name' => 'Peru',
                'area' => 1285216,
            ],
            1 => [
                'name' => 'Angola',
                'area' => 1246700,
            ],
            2 => [
                'name' => 'Mali',
                'area' => 1240192,
            ],
            3 => [
                'name' => 'South Africa',
                'area' => 1219090,
            ],
            4 => [
                'name' => 'Colombia',
                'area' => 1138910,
            ],
            5 => [
                'name' => 'Ethiopia',
                'area' => 1104300,
            ],
            6 => [
                'name' => 'Bolivia',
                'area' => 1098581,
            ],
            7 => [
                'name' => 'Mauritania',
                'area' => 1030700,
            ],
            8 => [
                'name' => 'Egypt',
                'area' => 1001450,
            ],
            9 => [
                'name' => 'Tanzania',
                'area' => 947300,
            ],
        ], $pagination->getItems());

        self::assertSame([
            0 => 1,
            1 => 2,
            2 => 3,
            3 => 4,
            4 => 5,
        ], $pagination->getPages());

        $pagination = $paginator->paginate(6);

        self::assertNotEmpty($pagination->getItems());
        self::assertSame([
            0 => [
                'name' => 'Spain',
                'area' => 505370,
            ],
            1 => [
                'name' => 'Turkmenistan',
                'area' => 488100,
            ],
            2 => [
                'name' => 'Cameroon',
                'area' => 475440,
            ],
            3 => [
                'name' => 'Papua New Guinea',
                'area' => 462840,
            ],
            4 => [
                'name' => 'Sweden',
                'area' => 450295,
            ],
            5 => [
                'name' => 'Uzbekistan',
                'area' => 447400,
            ],
            6 => [
                'name' => 'Morocco',
                'area' => 446550,
            ],
            7 => [
                'name' => 'Iraq',
                'area' => 438317,
            ],
            8 => [
                'name' => 'Paraguay',
                'area' => 406752,
            ],
            9 => [
                'name' => 'Zimbabwe',
                'area' => 390757,
            ],
        ], $pagination->getItems());

        self::assertSame([
            0 => 4,
            1 => 5,
            2 => 6,
            3 => 7,
            4 => 8,
        ], $pagination->getPages());

        $pagination = $paginator->paginate(50);
        self::assertCount(0, $pagination->getItems());
    }

    public function testPaginateFailZeroPageNumber(): void
    {
        $this->expectException(InvalidPageNumberException::class);
        $this->expectException(CallbackNotFoundException::class);
        $this->paginator->paginate(0);
    }

    public function testPaginateHighVolume(): void
    {
        $items = range(0, 293832);

        $this->paginator->setItemsPerPage(10)->setPagesInRange(5);

        $this->paginator->setItemTotalCallback(
            static function (Pagination $pagination) use ($items): int {
                $pagination->setMeta(['meta_3']);

                return \count($items);
            }
        );

        $this->paginator->setSliceCallback(
            /**
             * @param int<0, max> $offset
             * @param int<0, max> $length
             *
             * @return list<mixed>
             */
            static function (int $offset, int $length, Pagination $pagination) use ($items): array {
                $pagination->setMeta(array_merge($pagination->getMeta(), ['meta_4']));

                return \array_slice($items, $offset, $length);
            }
        );

        $pagination = $this->paginator->paginate();

        self::assertContains('meta_3', $pagination->getMeta());
        self::assertContains('meta_4', $pagination->getMeta());

        self::assertCount(10, $pagination->getItems());
        self::assertCount(5, $pagination->getPages());

        self::assertSame(29384, $pagination->getTotalNumberOfPages());
        self::assertSame(1, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(29384, $pagination->getLastPageNumber());

        self::assertNull($pagination->getPreviousPageNumber());

        self::assertSame(2, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(293833, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(5, $pagination->getLastPageNumberInRange());

        // Move to random page
        $pagination = $this->paginator->paginate(4573);

        self::assertCount(10, $pagination->getItems());
        self::assertCount(5, $pagination->getPages());

        self::assertSame(29384, $pagination->getTotalNumberOfPages());
        self::assertSame(4573, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(29384, $pagination->getLastPageNumber());
        self::assertSame(4572, $pagination->getPreviousPageNumber());
        self::assertSame(4574, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(293833, $pagination->getTotalNumberOfItems());
        self::assertSame(4571, $pagination->getFirstPageNumberInRange());
        self::assertSame(4575, $pagination->getLastPageNumberInRange());

        // Move to last page
        $pagination = $this->paginator->paginate(29384);

        self::assertCount(3, $pagination->getItems());
        self::assertCount(5, $pagination->getPages());

        self::assertSame(29384, $pagination->getTotalNumberOfPages());
        self::assertSame(29384, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(29384, $pagination->getLastPageNumber());
        self::assertSame(29383, $pagination->getPreviousPageNumber());

        self::assertNull($pagination->getNextPageNumber());

        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(293833, $pagination->getTotalNumberOfItems());
        self::assertSame(29380, $pagination->getFirstPageNumberInRange());
        self::assertSame(29384, $pagination->getLastPageNumberInRange());

        self::assertContains('meta_3', $pagination->getMeta());
        self::assertContains('meta_4', $pagination->getMeta());
    }

    public function testPaginateItemsPerPageNegativeOne(): void
    {
        $items = range(0, 1000);

        $this->paginator->setItemsPerPage(-1)->setPagesInRange(5);

        $this->paginator->setItemTotalCallback(static function (Pagination $pagination) use ($items): int {
            $pagination->setMeta(['meta_3']);

            return \count($items);
        });

        $this->paginator->setSliceCallback(static function (int $offset, int $length, Pagination $pagination) use ($items): array {
            $pagination->setMeta(array_merge($pagination->getMeta(), ['meta_4']));

            return \array_slice($items, $offset, $length);
        });

        $pagination = $this->paginator->paginate();

        self::assertContains('meta_3', $pagination->getMeta());
        self::assertContains('meta_4', $pagination->getMeta());

        self::assertCount(1001, $pagination->getItems());
        self::assertCount(1003, $pagination->getPages());

        self::assertSame(-1001, $pagination->getTotalNumberOfPages());
        self::assertSame(1, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(-1001, $pagination->getLastPageNumber());

        self::assertNull($pagination->getPreviousPageNumber());

        self::assertNull($pagination->getNextPageNumber());
        self::assertSame(-1, $pagination->getItemsPerPage());
        self::assertSame(1001, $pagination->getTotalNumberOfItems());
        self::assertSame(-1001, $pagination->getFirstPageNumberInRange());
        self::assertSame(1, $pagination->getLastPageNumberInRange());

        // Move to random page
        $pagination = $this->paginator->paginate(567);

        self::assertCount(1001, $pagination->getItems());
        self::assertCount(1003, $pagination->getPages());

        self::assertSame(-1001, $pagination->getTotalNumberOfPages());
        self::assertSame(567, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(-1001, $pagination->getLastPageNumber());
        self::assertSame(566, $pagination->getPreviousPageNumber());
        self::assertNull($pagination->getNextPageNumber());
        self::assertSame(-1, $pagination->getItemsPerPage());
        self::assertSame(1001, $pagination->getTotalNumberOfItems());
        self::assertSame(-1001, $pagination->getFirstPageNumberInRange());
        self::assertSame(1, $pagination->getLastPageNumberInRange());

        // Move to last page
        $pagination = $this->paginator->paginate(1003);

        self::assertCount(1001, $pagination->getItems());
        self::assertCount(1003, $pagination->getPages());

        self::assertSame(-1001, $pagination->getTotalNumberOfPages());
        self::assertSame(1003, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(-1001, $pagination->getLastPageNumber());
        self::assertSame(1002, $pagination->getPreviousPageNumber());

        self::assertNull($pagination->getNextPageNumber());

        self::assertSame(-1, $pagination->getItemsPerPage());
        self::assertSame(1001, $pagination->getTotalNumberOfItems());
        self::assertSame(-1001, $pagination->getFirstPageNumberInRange());
        self::assertSame(1, $pagination->getLastPageNumberInRange());

        self::assertContains('meta_3', $pagination->getMeta());
        self::assertContains('meta_4', $pagination->getMeta());
    }

    public function testPaginateLowVolume(): void
    {
        $items = range(0, 27);

        $this->paginator->setItemsPerPage(10)->setPagesInRange(5);

        $this->paginator->setItemTotalCallback(static function (Pagination $pagination) use ($items): int {
            $pagination->setMeta(['meta_1']);

            return \count($items);
        });

        $this->paginator->setSliceCallback(static function (int $offset, int $length, Pagination $pagination) use ($items): array {
            $pagination->setMeta(array_merge($pagination->getMeta(), ['meta_2']));

            return \array_slice($items, $offset, $length);
        });

        $pagination = $this->paginator->paginate();

        self::assertContains('meta_1', $pagination->getMeta());
        self::assertContains('meta_2', $pagination->getMeta());

        self::assertCount(10, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(1, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());

        self::assertNull($pagination->getPreviousPageNumber());

        self::assertSame(2, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());

        // Increment page
        $pagination = $this->paginator->paginate(2);

        self::assertCount(10, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(2, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());
        self::assertSame(1, $pagination->getPreviousPageNumber());
        self::assertSame(3, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());

        // Increment page
        $pagination = $this->paginator->paginate(3);

        self::assertCount(8, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(3, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());
        self::assertSame(2, $pagination->getPreviousPageNumber());

        self::assertNull($pagination->getNextPageNumber());

        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());

        self::assertContains('meta_1', $pagination->getMeta());
        self::assertContains('meta_2', $pagination->getMeta());
    }

    public function testPaginateLowVolumeConstructorConfig(): void
    {
        $items = range(0, 27);

        $paginator = new Paginator([
            'itemTotalCallback' => static fn (): int => \count($items),
            'sliceCallback'     => static fn (int $offset, int $length): array => \array_slice($items, $offset, $length),
            'itemsPerPage'      => 10,
            'pagesInRange'      => 5,
        ]);

        $pagination = $paginator->paginate();

        self::assertCount(10, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(1, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());

        self::assertNull($pagination->getPreviousPageNumber());

        self::assertSame(2, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());

        // Increment page
        $pagination = $paginator->paginate(2);

        self::assertCount(10, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(2, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());
        self::assertSame(1, $pagination->getPreviousPageNumber());
        self::assertSame(3, $pagination->getNextPageNumber());
        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());

        // Increment page
        $pagination = $paginator->paginate(3);

        self::assertCount(8, $pagination->getItems());
        self::assertCount(3, $pagination->getPages());

        self::assertSame(3, $pagination->getTotalNumberOfPages());
        self::assertSame(3, $pagination->getCurrentPageNumber());
        self::assertSame(1, $pagination->getFirstPageNumber());
        self::assertSame(3, $pagination->getLastPageNumber());
        self::assertSame(2, $pagination->getPreviousPageNumber());

        self::assertNull($pagination->getNextPageNumber());

        self::assertSame(10, $pagination->getItemsPerPage());
        self::assertSame(28, $pagination->getTotalNumberOfItems());
        self::assertSame(1, $pagination->getFirstPageNumberInRange());
        self::assertSame(3, $pagination->getLastPageNumberInRange());
    }

    public function testPaginationIterator(): void
    {
        $items = range(0, 27);

        $paginator = new Paginator([
            'itemTotalCallback' => static fn (): int => \count($items),
            'sliceCallback'     => static fn (int $offset, int $length): ArrayIterator => new ArrayIterator(\array_slice($items, $offset, $length)),
            'itemsPerPage'      => 15,
            'pagesInRange'      => 5,
        ]);

        $pagination = $paginator->paginate();

        self::assertCount(15, $pagination);

        foreach ($pagination as $i => $item) {
            self::assertSame($i, $item);
        }
    }

    public function testPaginationIteratorAggregate(): void
    {
        $items = range(0, 27);

        $paginator = new Paginator([
            'itemTotalCallback' => static fn (): int => \count($items),
            'sliceCallback'     => static fn (int $offset, int $length): array => \array_slice($items, $offset, $length),
            'itemsPerPage'      => 15,
            'pagesInRange'      => 5,
        ]);

        $pagination = $paginator->paginate();

        self::assertCount(15, $pagination);

        $iterations = 0;

        foreach ($pagination as $i => $item) {
            self::assertSame($i, $item);

            ++$iterations;
        }

        self::assertSame(15, $iterations);
    }

    public function testSetItemsPerPage(): void
    {
        $this->paginator->setItemsPerPage(45);

        self::assertSame(45, $this->paginator->getItemsPerPage());
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testSetItemTotalCallback(): void
    {
        /**
         * @phpstan-ignore argument.type
         */
        $this->paginator->setItemTotalCallback(static fn (): string => 'item_total_callback');

        $callback = $this->paginator->getItemTotalCallback();

        self::assertInstanceOf(\Closure::class, $callback);

        /**
         * @phpstan-ignore staticMethod.impossibleType
         */
        self::assertSame('item_total_callback', $callback());
    }

    public function testSetPagesInRange(): void
    {
        $this->paginator->setPagesInRange(23);

        self::assertSame(23, $this->paginator->getPagesInRange());
    }

    /**
     * @psalm-suppress InvalidArgument
     * @psalm-suppress TooFewArguments
     */
    public function testSetSliceCallback(): void
    {
        /**
         * @phpstan-ignore argument.type
         */
        $this->paginator->setSliceCallback(static fn (): string => 'slice_callback');

        $callback = $this->paginator->getSliceCallback();

        self::assertInstanceOf(\Closure::class, $callback);

        /**
         * @phpstan-ignore staticMethod.impossibleType, arguments.count
         */
        self::assertSame('slice_callback', $callback());
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function testSliceCallbackNotFound(): void
    {
        $this->paginator->setItemsPerPage(10)->setPagesInRange(5);

        $this->expectException(CallbackNotFoundException::class);
        /**
         * @phpstan-ignore argument.type
         */
        $this->paginator->setItemTotalCallback(static fn (): string => 'item_total_callback');
        $this->paginator->setSliceCallback(null);
        $this->paginator->paginate();
    }
}
