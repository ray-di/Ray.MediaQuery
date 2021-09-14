<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;
use Ray\MediaQuery\Entity\Invoice;

class CamelCaseTraitTest extends TestCase
{
    public function testRequest(): void
    {
        $user = new Invoice();
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $user->user_name = '1'; // @phpstan-ignore-line
        $this->assertSame($user->userName, '1');
    }
}
