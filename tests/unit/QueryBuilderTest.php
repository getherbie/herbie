<?php

namespace herbie\tests\unit;

use Codeception\Test\Unit;
use herbie\Page;
use herbie\Pagination;
use herbie\QueryBuilder;
use InvalidArgumentException;

final class QueryBuilderTest extends Unit
{
    /**
     * @param array<int, array<string, mixed>> $data
     */
    private function createBuilder(array $data): QueryBuilder
    {
        return (new QueryBuilder())->from($data);
    }

    private function createData(): array
    {
        return [
            ['title' => 'Alpha', 'type' => 'post', 'views' => 10, 'tags' => ['php', 'cms']],
            ['title' => 'Beta', 'type' => 'page', 'views' => 5, 'tags' => ['php']],
            ['title' => 'Gamma', 'type' => 'post', 'views' => 20, 'tags' => ['js']],
            ['title' => 'Delta', 'type' => 'page', 'views' => 15, 'tags' => ['cms']],
        ];
    }

    public function testFromArray(): void
    {
        $builder = $this->createBuilder($this->createData());

        $this->assertCount(4, $builder->all());
    }

    public function testFromIterator(): void
    {
        $data = new \ArrayIterator($this->createData());
        $builder = (new QueryBuilder())->from($data);

        $this->assertCount(4, $builder->all());
    }

    public function testWhereWithoutConditionsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBuilder($this->createData())->where();
    }

    public function testWhereEqual(): void
    {
        $result = $this->createBuilder($this->createData())->where('type=post')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereNotEqual(): void
    {
        $result = $this->createBuilder($this->createData())->where('type!=post')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereGreaterThan(): void
    {
        $result = $this->createBuilder($this->createData())->where('views>10')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereLessThan(): void
    {
        $result = $this->createBuilder($this->createData())->where('views<10')->all();

        $this->assertCount(1, $result);
    }

    public function testWhereGreaterThanEqual(): void
    {
        $result = $this->createBuilder($this->createData())->where('views>=10')->all();

        $this->assertCount(3, $result);
    }

    public function testWhereLessThanEqual(): void
    {
        $result = $this->createBuilder($this->createData())->where('views<=10')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereContains(): void
    {
        $result = $this->createBuilder($this->createData())->where('title*=a')->all();

        // every title contains an "a" (case insensitive)
        $this->assertCount(4, $result);
    }

    public function testWhereStarts(): void
    {
        $result = $this->createBuilder($this->createData())->where('title^=Al')->all();

        $this->assertCount(1, $result);
    }

    public function testWhereEnds(): void
    {
        $result = $this->createBuilder($this->createData())->where('title$=ta')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereRegex(): void
    {
        $result = $this->createBuilder($this->createData())->where('title?=/^[AB]/')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereContainsWords(): void
    {
        $data = [
            ['title' => 'a nice day'],
            ['title' => 'a terrible day'],
            ['title' => 'a night'],
        ];
        $result = (new QueryBuilder())->from($data)->where('title~=nice day')->all();

        $this->assertCount(1, $result);
    }

    public function testWhereOnArrayField(): void
    {
        $result = $this->createBuilder($this->createData())->where('tags=php')->all();

        $this->assertCount(2, $result);
    }

    public function testWhereWithOrValues(): void
    {
        $result = $this->createBuilder($this->createData())->where('title=Alpha|Gamma')->all();

        $this->assertCount(2, $result);
    }

    public function testMultipleWhereAreCombinedWithAnd(): void
    {
        $result = $this->createBuilder($this->createData())
            ->where('type=post')
            ->where('views>10')
            ->all();

        $this->assertCount(1, $result);
    }

    public function testWhereOperatorFormat(): void
    {
        $result = $this->createBuilder($this->createData())
            ->where(['OR', 'title=Alpha', 'title=Gamma'])
            ->all();

        $this->assertCount(2, $result);
    }

    public function testWhereHashFormat(): void
    {
        $result = $this->createBuilder($this->createData())->where(['type' => 'page'])->all();

        $this->assertCount(2, $result);
    }

    public function testWhereHashFormatWithArrayValue(): void
    {
        $result = $this->createBuilder($this->createData())->where(['tags' => ['php']])->all();

        $this->assertSame(['Alpha', 'Beta'], array_values(array_column($result, 'title')));

        $result = $this->createBuilder($this->createData())->where(['tags' => ['php', 'js']])->all();

        $this->assertSame(['Alpha', 'Beta', 'Gamma'], array_values(array_column($result, 'title')));
    }

    public function testWhereUnsupportedOperatorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBuilder($this->createData())->where('title Alpha');
    }

    public function testWhereUnsupportedConditionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createBuilder($this->createData())->where(42);
    }

    public function testOrderAscending(): void
    {
        $result = $this->createBuilder($this->createData())->order('title')->all();

        $titles = array_column($result, 'title');
        $this->assertSame(['Alpha', 'Beta', 'Delta', 'Gamma'], array_values($titles));
    }

    public function testOrderDescending(): void
    {
        $result = $this->createBuilder($this->createData())->order('-title')->all();

        $titles = array_values(array_column($result, 'title'));
        $this->assertSame(['Gamma', 'Delta', 'Beta', 'Alpha'], $titles);
    }

    public function testOrderWorksWithArrayAccessItems(): void
    {
        $builder = (new QueryBuilder())->from([
            new Page(['title' => 'Beta']),
            new Page(['title' => 'Alpha']),
        ]);

        $titles = array_map(
            static fn(Page $page): string => $page['title'],
            $builder->order('title')->all(),
        );

        $this->assertSame(['Alpha', 'Beta'], $titles);
    }

    public function testLimit(): void
    {
        $result = $this->createBuilder($this->createData())->limit(2)->all();

        $this->assertCount(2, $result);
    }

    public function testOffset(): void
    {
        $result = $this->createBuilder($this->createData())->offset(2)->all();

        $this->assertCount(2, $result);
    }

    public function testLimitAndOffset(): void
    {
        $result = $this->createBuilder($this->createData())->limit(1)->offset(1)->all();

        $this->assertCount(1, $result);
        $this->assertSame('Beta', reset($result)['title']);
    }

    public function testOffsetIsAppliedAfterWhere(): void
    {
        $result = $this->createBuilder($this->createData())->where('type=page')->offset(1)->all();

        $this->assertSame(['Delta'], array_values(array_column($result, 'title')));
    }

    public function testCount(): void
    {
        $builder = $this->createBuilder($this->createData())->where('type=post');

        // count() returns the number of matching records without needing all()
        $this->assertSame(2, $builder->count());
    }

    public function testCountIsIdempotent(): void
    {
        $builder = $this->createBuilder($this->createData())->where('type=post');

        $this->assertSame(2, $builder->count());
        $this->assertSame(2, $builder->count());
    }

    public function testOne(): void
    {
        $result = $this->createBuilder($this->createData())->where('title=Gamma')->one();

        $this->assertSame('Gamma', $result['title']);
    }

    public function testOneReturnsNullIfNotFound(): void
    {
        $result = $this->createBuilder($this->createData())->where('title=NotFound')->one();

        $this->assertNull($result);
    }

    public function testOneDoesNotLimitSubsequentQueries(): void
    {
        $builder = $this->createBuilder($this->createData());

        $this->assertSame('Alpha', $builder->one()['title']);
        $this->assertCount(4, $builder->all());
        $this->assertSame(4, $builder->count());
    }

    public function testGetIterator(): void
    {
        $builder = $this->createBuilder($this->createData())->where('type=post');

        $count = 0;
        foreach ($builder as $item) {
            $count++;
        }
        $this->assertSame(2, $count);
    }

    public function testPaginate(): void
    {
        $pagination = $this->createBuilder($this->createData())->paginate(2);

        $this->assertInstanceOf(Pagination::class, $pagination);
        $this->assertSame(4, $pagination->getTotalItems());
        $this->assertSame(2, $pagination->count());
    }

    public function testPaginateKeepsConfiguredLimit(): void
    {
        $builder = $this->createBuilder($this->createData())->limit(2);
        $pagination = $builder->paginate(2);

        $this->assertSame(4, $pagination->getTotalItems());
        $this->assertCount(2, $builder->all());
    }

    public function testPaginateUsesPageFromRequestByDefault(): void
    {
        $_GET['page'] = '2';
        try {
            $pagination = $this->createBuilder($this->createData())->paginate(2);
            $this->assertSame(2, $pagination->getCurrentPage());
        } finally {
            unset($_GET['page']);
        }
    }

    public function testPaginateUsesExplicitPage(): void
    {
        $_GET['page'] = '1';
        try {
            $pagination = $this->createBuilder($this->createData())->paginate(2, 2);
            $this->assertSame(2, $pagination->getCurrentPage());
        } finally {
            unset($_GET['page']);
        }
    }

    public function testWhereOnMissingFieldIsFalse(): void
    {
        $result = $this->createBuilder($this->createData())->where('notexisting=value')->all();

        $this->assertCount(0, $result);
    }
}
