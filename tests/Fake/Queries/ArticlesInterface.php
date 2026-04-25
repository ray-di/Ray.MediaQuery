<?php

declare(strict_types=1);

namespace Ray\MediaQuery\Queries;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Entity\Article;
use Ray\MediaQuery\Entity\TodoConstruct;
use Ray\MediaQuery\Factory\TodoEntityFactory;
use Ray\MediaQuery\Result\Articles;

interface ArticlesInterface
{
    /**
     * Rows come back as associative arrays (no entity hydration).
     *
     * @return Articles<array<string, mixed>>
     */
    #[DbQuery('article_list')]
    public function listAssoc(): Articles;

    /**
     * Rows come back as `Article` instances via docblock-driven entity hydration.
     *
     * @return Articles<Article>
     */
    #[DbQuery('article_list')]
    public function listHydrated(): Articles;

    /**
     * Rows come back as `TodoConstruct` instances via the `factory:` attribute.
     *
     * @return Articles<TodoConstruct>
     */
    #[DbQuery('article_list', factory: TodoEntityFactory::class)]
    public function listViaFactory(): Articles;

    /**
     * SELECT that matches no rows — `$rows` is `[]`, but the wrapper is still
     * an `Articles`, not null.
     *
     * @return Articles<array<string, mixed>>
     */
    #[DbQuery('article_list_empty')]
    public function listEmpty(): Articles;
}
