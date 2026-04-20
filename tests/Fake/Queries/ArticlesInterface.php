<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Article;
use Ray\MediaQuery\Result\Articles;

interface ArticlesInterface
{
    /**
     * Rows come back as associative arrays (no entity hydration).
     */
    #[DbQuery('todo_list')]
    public function listAssoc(): Articles;

    /**
     * Rows come back as `Article` instances via docblock-driven entity hydration.
     *
     * @return Articles<Article>
     */
    #[DbQuery('todo_list')]
    public function listHydrated(): Articles;
}
