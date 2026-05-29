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

        // PDO::FETCH_CLASS hydrates snake_case columns by invoking __set().
        $entity->__set('user_name', 'John Doe');
        $entity->__set('email_address', 'john@example.com');

        $this->assertSame('John Doe', $entity->userName);
        $this->assertSame('john@example.com', $entity->emailAddress);
    }
}
