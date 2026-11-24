<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('author_profile', type: 'row', factory: AuthorProfileFactory::class)]
    public function profile(int $id): AuthorProfile|null;
}
