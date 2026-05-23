<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Aura\Sql\ExtendedPdoInterface;
use Composer\Autoload\ClassLoader;
use DateTimeImmutable;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryModule;
use Ray\MediaQuery\Queries;

/** @var ClassLoader $loader */
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('Tutorial\\Blog\\', __DIR__ . '/Blog');

$sqlDir = __DIR__ . '/sql';
$dsn = 'sqlite::memory:';

$injector = new Injector(new class ($sqlDir, $dsn) extends AbstractModule {
    public function __construct(
        private readonly string $sqlDir,
        private readonly string $dsn,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $queries = Queries::fromClasses([
            ArticleQueryInterface::class,
            CommentQueryInterface::class,
        ]);
        $this->install(new MediaQueryModule($queries, [new DbQueryConfig($this->sqlDir)]));
        $this->install(new AuraSqlModule($this->dsn));
        $this->bind(MarkdownExcerpter::class);
    }
});

/** @var ExtendedPdoInterface $pdo */
$pdo = $injector->getInstance(ExtendedPdoInterface::class);
foreach (preg_split('/;\\s*/', trim((string) file_get_contents(__DIR__ . '/schema.sql'))) ?: [] as $stmt) {
    if ($stmt !== '') {
        $pdo->query($stmt);
    }
}

/** @var ArticleQueryInterface $repo */
$repo = $injector->getInstance(ArticleQueryInterface::class);
/** @var CommentQueryInterface $commentRepo */
$commentRepo = $injector->getInstance(CommentQueryInterface::class);

echo "=== Ch.3 / Ch.10: INSERT (InsertedRow) ===\n";
$first = $repo->add(
    title: 'Hello, Ray.MediaQuery',
    body: 'This is the first post about interface-driven SQL.',
    authorName: 'Alice',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-01 09:00:00'),
    createdAt: new DateTimeImmutable('2026-04-01 09:00:00'),
);
printf("inserted id=%s, status=%s\n", (string) $first->id, (string) $first->values['status']);

$second = $repo->add(
    title: 'Second Post',
    body: 'About SQL and Objects living in harmony.',
    authorName: 'Bob',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-02 10:00:00'),
    createdAt: new DateTimeImmutable('2026-04-02 10:00:00'),
);
printf("inserted id=%s\n\n", (string) $second->id);

echo "=== Ch.1 / Ch.4 / Ch.5: SELECT list as Article entities ===\n";
$articles = $repo->list();
printf("list() returned %d articles\n", count($articles));
printf("First: id=%d title='%s' authorName='%s'\n\n", $articles[0]->id, $articles[0]->title, $articles[0]->authorName);

echo "=== Ch.2 / Ch.6: SELECT row + ArticleId (ToScalarInterface) ===\n";
$article = $repo->item(new ArticleId(1));
assert($article !== null);
printf("item(ArticleId(1)) -> '%s' published_at=%s\n\n", $article->title, (string) $article->publishedAt);

echo "=== Ch.7 / Ch.8: factory with DI (ArticleStats) ===\n";
$commentRepo->add(1, 'Great post!', new DateTimeImmutable('2026-04-01 12:00:00'));
$commentRepo->add(1, 'Thanks for sharing.', new DateTimeImmutable('2026-04-01 13:00:00'));
$stats = $repo->stats(new ArticleId(1));
printf("stats: title='%s' commentCount=%d published=%s\n", $stats->title, $stats->commentCount, $stats->published ? 'true' : 'false');
printf("excerpt='%s'\n\n", $stats->excerpt);

echo "=== Ch.9: AffectedRows ===\n";
$updated = $repo->update(new ArticleId(1), 'Hello, Ray.MediaQuery (edited)', 'Updated body.');
printf("update affected=%d isAffected=%s\n", $updated->count, $updated->isAffected() ? 'true' : 'false');
$deleted = $repo->delete(new ArticleId(2));
printf("delete affected=%d isAffected=%s\n\n", $deleted->count, $deleted->isAffected() ? 'true' : 'false');

echo "=== Ch.11: Pager (Pages<Article>) ===\n";
for ($i = 3; $i <= 32; $i++) {
    $repo->add(
        title: "Post #{$i}",
        body: "Body for post {$i}.",
        authorName: 'Carol',
        status: 'published',
        publishedAt: new DateTimeImmutable('2026-04-03 00:00:00'),
        createdAt: new DateTimeImmutable('2026-04-03 00:00:00'),
    );
}
$pages = $repo->paginated();
$page1 = $pages[1];
printf("total items=%d, current=%d, hasNext=%s\n", count($pages), $page1->current, $page1->hasNext ? 'true' : 'false');
printf("page 1 has %d items, first title='%s'\n\n", count($page1->data), $page1->data[0]->title);

echo "=== Ch.11 / Ray.MediaQuery 1.1: Pager + factory hydration ===\n";
$statsPages = $repo->statsPaginated();
$statsPage1 = $statsPages[1];
$firstStats = $statsPage1->data[0];
assert($firstStats instanceof ArticleStats);
printf("first stats row=%s commentCount=%d excerpt='%s'\n\n", $firstStats::class, $firstStats->commentCount, $firstStats->excerpt);

echo "=== Ch.12: custom PostQueryInterface (ArticleSearchResult) ===\n";
$result = $repo->search('%Post%');
printf("matched=%d, sql contains 'LIKE'=%s\n", $result->matched, str_contains($result->sql, 'LIKE') ? 'yes' : 'no');
printf("first hit: id=%d title='%s'\n\n", $result->rows[0]->id, $result->rows[0]->title);

echo "=== Conclusion / Ray.MediaQuery 1.1: DML + SELECT PostQuery ===\n";
$created = $repo->createAndGet(
    title: 'Created and fetched',
    body: 'A multi-statement query can return the row created by its first statement.',
    authorName: 'Eve',
);
printf("created article id=%d title='%s' status=%s\n\n", $created->article->id, $created->article->title, $created->article->status);

echo "All chapters executed successfully.\n";
