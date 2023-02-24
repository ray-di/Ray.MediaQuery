<?php

declare(strict_types=1);

namespace Ray\MediaQuery;
use Ray\MediaQuery\Entity\FakeEntity;

final class FakeReturn
{
    public function item(): FakeEntity
    {
        return new FakeEntity('1');
    }

    /**
     * @return array<FakeEntity>
     */
    public function list(): array
    {
        return [
            new FakeEntity('1')
        ];
    }

    public function arrayList(): array
    {
        return [];
    }

    public function noReturn()
    {
    }

    public function noPhpDoc(): Pages
    {
    }

    public function noPhpDocFakePages(): FakePages
    {
    }

    /**
     * @todo Add return type!
     */
    public function noReturnDoc(): Pages
    {
    }

    /** @todo Add return type! */
    public function noReturnDocFakePages(): FakePages
    {
    }

    /**
     * @return array<int>
     */
    public function nonEntityGeneric(): array
    {
    }

    /**
     * @return int
     */
    public function invalidReturnType(): array
    {
    }

    /**
     * @return array<array<string, string>>
     */
    public function returnArray(): array
    {
    }
}
