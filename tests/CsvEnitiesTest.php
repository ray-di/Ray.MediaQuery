<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Entity\Memo;

class CsvEnitiesTest extends TestCase
{
    public function testInvoke(): void
    {
        $memos = (new CsvEnities())(Memo::class, '1,2', 'run,walk');
        $this->assertContainsOnlyInstancesOf(
            className: Memo::class,
            haystack: $memos,
        );
        $this->assertSame(['1', 'run'], [$memos[0]->id, $memos[0]->body]);
        $this->assertSame(['2', 'walk'], [$memos[1]->id, $memos[1]->body]);
    }
}
