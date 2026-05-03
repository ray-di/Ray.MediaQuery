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
        $pages = new MappedPages(
            $this->pages($page),
            static fn (array $row): array => ['title' => $row['title']],
        );

        $mappedPage = $pages[1];

        $this->assertTrue(isset($pages[1]));
        $this->assertInstanceOf(Page::class, $mappedPage);
        $data = $mappedPage->data;
        $this->assertIsArray($data);
        $this->assertIsArray($data[0]);
        $this->assertSame('run', $data[0]['title']);
        $this->assertSame('already-mapped', $data[1]);
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

        $this->assertSame(1, $pages->count());
    }

    private function page(mixed $data): Page
    {
        $reflection = new ReflectionClass(Page::class);
        $page = $reflection->newInstanceWithoutConstructor();
        $page->data = $data;

        return $page;
    }

    private function pages(mixed $value): PagesInterface
    {
        return new class ($value) implements PagesInterface {
            public mixed $setValue = null;
            public bool $unsetCalled = false;

            public function __construct(
                private mixed $value,
            ) {
            }

            public function offsetExists(mixed $offset): bool
            {
                unset($offset);

                return true;
            }

            public function offsetGet(mixed $offset): mixed
            {
                unset($offset);

                return $this->value;
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
                unset($offset);

                $this->setValue = $value;
            }

            public function offsetUnset(mixed $offset): void
            {
                unset($offset);

                $this->unsetCalled = true;
            }

            public function count(): int
            {
                return 1;
            }
        };
    }
}
