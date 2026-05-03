<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\Page;
use ReflectionClass;

final class MappedPagesTest extends TestCase
{
    public function testMapsPageData(): void
    {
        $page = $this->page([['id' => '1', 'title' => 'run'], 'already-mapped']);
        $calls = 0;
        $pages = new MappedPages(
            $this->pages($page),
            static function (array $row) use (&$calls): array {
                $calls++;

                return ['title' => $row['title'], 'calls' => $calls];
            },
        );

        $this->assertTrue(isset($pages[1]));
        $mappedPage = $pages[1];

        $this->assertInstanceOf(Page::class, $mappedPage);
        $data = $mappedPage->data;
        $this->assertIsArray($data);
        $this->assertIsArray($data[0]);
        $this->assertSame('run', $data[0]['title']);
        $this->assertSame(1, $data[0]['calls']);
        $this->assertSame('already-mapped', $data[1]);
        $this->assertSame([['id' => '1', 'title' => 'run'], 'already-mapped'], $page->data);
    }

    public function testReturnsNonPageValuesAsIs(): void
    {
        $pages = new MappedPages(
            $this->pages('not-page'),
            static fn (array $row): array => $row,
        );

        $this->assertSame('not-page', $pages[1]);
    }

    public function testDelegatesMutationAndCount(): void
    {
        $delegate = $this->pages($this->page([]));
        $pages = new MappedPages(
            $delegate,
            static fn (array $row): array => $row,
        );

        $pages[1] = 'set';
        unset($pages[1]);

        $this->assertSame('set', $delegate->setValue);
        $this->assertTrue($delegate->unsetCalled);
        $this->assertSame(1, $pages->count());
    }

    private function page(mixed $data): Page
    {
        $reflection = new ReflectionClass(Page::class);
        $page = $reflection->newInstanceWithoutConstructor();
        $page->data = $data;

        return $page;
    }

    private function pages(mixed $value): MappedPagesFakePages
    {
        return new MappedPagesFakePages($value);
    }
}
