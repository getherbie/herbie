<?php

namespace herbie\tests\unit;

use ArrayIterator;
use Codeception\Test\Unit;
use herbie\PageList;
use herbie\Pagination;
use InvalidArgumentException;

final class PaginationTest extends Unit
{
    /**
     * @return array<int, string>
     */
    private function createItems(int $count): array
    {
        $items = [];
        for ($i = 1; $i <= $count; $i++) {
            $items[] = 'item' . $i;
        }
        return $items;
    }

    public function testTotalItems(): void
    {
        $pagination = new Pagination($this->createItems(25), 10);

        $this->assertSame(25, $pagination->getTotalItems());
    }

    public function testFirstPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10);

        $this->assertSame(1, $pagination->getCurrentPage());
        $this->assertSame(0, $pagination->getOffset());
        $this->assertCount(10, $pagination->getItems());
        $this->assertSame('item1', $pagination->getItems()[0]);
    }

    public function testLastPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10);

        $this->assertSame(3, $pagination->getLastPage());
    }

    public function testSecondPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 2);

        $this->assertSame(2, $pagination->getCurrentPage());
        $this->assertSame(10, $pagination->getOffset());
        $this->assertSame('item11', $pagination->getItems()[0]);
    }

    public function testLastPageHasRemainingItems(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 3);

        $this->assertSame(3, $pagination->getCurrentPage());
        $this->assertCount(5, $pagination->getItems());
        $this->assertSame('item21', $pagination->getItems()[0]);
    }

    public function testPageNumberIsClampedToTheLastPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 99);

        $this->assertSame(3, $pagination->getCurrentPage());
    }

    public function testPageNumberIsClampedToTheFirstPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, -5);

        $this->assertSame(1, $pagination->getCurrentPage());
    }

    public function testNextAndPreviousPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 2);

        $this->assertSame(3, $pagination->getNextPage());
        $this->assertSame(1, $pagination->getPreviousPage());
    }

    public function testNextPageOnLastPageStaysOnLastPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 3);

        $this->assertSame(3, $pagination->getNextPage());
    }

    public function testPreviousPageOnFirstPageStaysOnFirstPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 1);

        $this->assertSame(1, $pagination->getPreviousPage());
    }

    public function testSetCurrentPage(): void
    {
        $pagination = new Pagination($this->createItems(25), 10);
        $pagination->setCurrentPage(2);

        $this->assertSame(2, $pagination->getCurrentPage());
        $this->assertSame(10, $pagination->getOffset());
    }

    public function testLimitIsClampedToTheNumberOfItems(): void
    {
        $pagination = new Pagination($this->createItems(5), 100);

        // the limit cannot be higher than the number of items
        $this->assertSame(5, $pagination->getLimit());
        $this->assertCount(5, $pagination->getItems());
    }

    public function testLimitLessThanOneUsesAllItems(): void
    {
        $pagination = new Pagination($this->createItems(7), 0);

        $this->assertSame(7, $pagination->getLimit());
        $this->assertCount(7, $pagination->getItems());
    }

    public function testIteration(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 2);

        $items = [];
        foreach ($pagination as $item) {
            $items[] = $item;
        }
        $this->assertCount(10, $items);
        $this->assertSame('item11', $items[0]);
    }

    public function testCount(): void
    {
        $pagination = new Pagination($this->createItems(25), 10, 2);

        $this->assertSame(10, $pagination->count());
    }

    public function testAcceptsIteratorAggregate(): void
    {
        $pageList = new PageList();
        $pageList->addItem(new \herbie\Page(['route' => 'a']));
        $pageList->addItem(new \herbie\Page(['route' => 'b']));

        $pagination = new Pagination($pageList, 10);

        $this->assertSame(2, $pagination->getTotalItems());
    }

    public function testAcceptsIterator(): void
    {
        $pagination = new Pagination(new ArrayIterator($this->createItems(3)), 10);

        $this->assertSame(3, $pagination->getTotalItems());
        $this->assertCount(3, $pagination->getItems());
    }

    public function testAcceptsGenerator(): void
    {
        $generator = (static function (): \Generator {
            yield 'item1';
            yield 'item2';
        })();

        $pagination = new Pagination($generator, 10);

        $this->assertSame(2, $pagination->getTotalItems());
    }

    public function testEmptyList(): void
    {
        $pagination = new Pagination([], 10);

        $this->assertSame(0, $pagination->getTotalItems());
        $this->assertSame(10, $pagination->getLimit());
        $this->assertSame(1, $pagination->getCurrentPage());
        $this->assertSame(1, $pagination->getFirstPage());
        $this->assertSame(1, $pagination->getLastPage());
        $this->assertSame(0, $pagination->count());
        $this->assertSame(0, $pagination->getOffset());
        $this->assertSame([], $pagination->getItems());
    }

    public function testEmptyListCanBeNavigated(): void
    {
        $pagination = new Pagination([], 10);

        $this->assertSame(1, $pagination->getNextPage());
        $this->assertSame(1, $pagination->getPreviousPage());
    }

    public function testNonIterableThrows(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        new Pagination('not iterable');
    }
}
