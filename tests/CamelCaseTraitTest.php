<?php

declare(strict_types=1);

namespace Ray\MediaQuery;

use PHPUnit\Framework\TestCase;

class CamelCaseTraitTest extends TestCase
{
    public function testSetConvertsSnakeCaseToCamelCaseProperty(): void
    {
        $entity = new class {
            use CamelCaseTrait;

            public string $userName = '';
            public string $emailAddress = '';
        };

        $nameKey = 'user_name';
        $emailKey = 'email_address';
        $entity->{$nameKey} = 'John Doe';
        $entity->{$emailKey} = 'john@example.com';

        $this->assertSame('John Doe', $entity->userName);
        $this->assertSame('john@example.com', $entity->emailAddress);
    }
}
