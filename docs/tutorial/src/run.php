<?php

declare(strict_types=1);

namespace Tutorial\Blog;

use Aura\Sql\ExtendedPdoInterface;
use Composer\Autoload\ClassLoader;
use DateTimeImmutable;
use DateTimeInterface;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\AuraSqlModule\Pagerfanta\Page;
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
            AuthorQueryInterface::class,
        ]);
        $this->install(new MediaQueryModule($queries, [new DbQueryConfig($this->sqlDir)]));
        $this->install(new AuraSqlModule($this->dsn));
        $this->bind(MarkdownExcerpter::class);
        // Pin the clock so the BDR `age` output is reproducible. In production,
        // MediaQueryModule's DateTimeImmutable binding resolves to the real time.
        $this->bind(DateTimeInterface::class)->toInstance(new DateTimeImmutable('2026-06-06'));
    }
});

/** @var ExtendedPdoInterface $pdo */
$pdo = $injector->getInstance(ExtendedPdoInterface::class);
foreach (preg_split('/;\\s*/', trim((string) file_get_contents(__DIR__ . '/schema.sql'))) ?: [] as $stmt) {
    if ($stmt !== '') {
        $pdo->query($stmt);
    }
}

/** @var ArticleQueryInterface $articleQuery */
$articleQuery = $injector->getInstance(ArticleQueryInterface::class);
/** @var CommentQueryInterface $commentQuery */
$commentQuery = $injector->getInstance(CommentQueryInterface::class);

echo "=== Ch.3 / Ch.10: INSERT (InsertedRow) ===\n";
$first = $articleQuery->add(
    title: 'Hello, Ray.MediaQuery',
    body: 'This is the first post about interface-driven SQL.',
    authorName: 'Alice',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-01 09:00:00'),
    createdAt: new DateTimeImmutable('2026-04-01 09:00:00'),
);
$firstStatus = $first->values['status'] ?? null;
assert(is_string($firstStatus));
printf("inserted id=%s, status=%s\n", $first->id, $firstStatus);

$second = $articleQuery->add(
    title: 'Second Post',
    body: 'About SQL and Objects living in harmony.',
    authorName: 'Bob',
    status: 'published',
    publishedAt: new DateTimeImmutable('2026-04-02 10:00:00'),
    createdAt: new DateTimeImmutable('2026-04-02 10:00:00'),
);
printf("inserted id=%s\n\n", $second->id);

echo "=== Ch.1 / Ch.4 / Ch.5: SELECT list as Article entities ===\n";
$articles = $articleQuery->list();
printf("list() returned %d articles\n", count($articles));
printf("First: id=%d title='%s' authorName='%s'\n\n", $articles[0]->id, $articles[0]->title, $articles[0]->authorName);

echo "=== Ch.2 / Ch.6: SELECT row + ArticleId (ToScalarInterface) ===\n";
$article = $articleQuery->item(new ArticleId(1));
assert($article !== null);
printf("item(ArticleId(1)) -> '%s' published_at=%s\n\n", $article->title, $article->publishedAt);

echo "=== Ch.7 / Ch.8: factory with DI (ArticleStats) + Comment hydration ===\n";
$commentQuery->add(1, 'Great post!', new DateTimeImmutable('2026-04-01 12:00:00'));
$commentQuery->add(1, 'Thanks for sharing.', new DateTimeImmutable('2026-04-01 13:00:00'));
$stats = $articleQuery->stats(new ArticleId(1));
printf("commentCount=%d, excerpt='%s'\n", $stats->commentCount, $stats->excerpt);
$comments = $commentQuery->listFor(1);
printf("comments=%d, first body='%s' (id=%d)\n\n", count($comments), $comments[0]->body, $comments[0]->id);

echo "=== Ch.8 / BDR: age from birth_date ===\n";
$pdo->perform('INSERT INTO author (name, birth_date) VALUES (?, ?)', ['Alice', '1990-06-15']);
/** @var AuthorQueryInterface $authorQuery */
$authorQuery = $injector->getInstance(AuthorQueryInterface::class);
$profile = $authorQuery->profile(1);
assert($profile !== null);
printf("name=%s birth_date=%s age=%d\n\n", $profile->name, $profile->birthDate, $profile->age);

echo "=== Ch.9: AffectedRows ===\n";
$updated = $articleQuery->update(new ArticleId(1), 'Hello, Ray.MediaQuery (edited)', 'Updated body.');
printf("updated count=%d, isAffected=%s\n", $updated->count, $updated->isAffected() ? 'yes' : 'no');
$deleted = $articleQuery->delete(new ArticleId(2));
printf("deleted count=%d\n\n", $deleted->count);

echo "=== Ch.11: Pager (Pages<Article>) ===\n";
for ($i = 3; $i <= 32; $i++) {
    $articleQuery->add(
        title: "Post #$i",
        body: "Body for post $i.",
        authorName: 'Carol',
        status: 'published',
        publishedAt: new DateTimeImmutable('2026-04-03 00:00:00'),
        createdAt: new DateTimeImmutable('2026-04-03 00:00:00'),
    );
}
$pages = $articleQuery->paginated();
$page1 = $pages[1];
assert($page1 instanceof Page);
assert(is_array($page1->data));
$firstPageArticle = $page1->data[0] ?? null;
assert($firstPageArticle instanceof Article);
printf("total items=%d\n", count($pages));
printf("page 1 has %d items, hasNext=%s\n", count($page1->data), $page1->hasNext ? 'yes' : 'no');
echo $firstPageArticle->title, "\n\n";

echo "=== Ch.11 / Ray.MediaQuery 1.1: Pager + factory hydration ===\n";
$statsPages = $articleQuery->statsPaginated();
$statsPage1 = $statsPages[1];
assert($statsPage1 instanceof Page);
assert(is_array($statsPage1->data));
$firstStats = $statsPage1->data[0] ?? null;
assert($firstStats instanceof ArticleStats);
printf("first stats row=%s commentCount=%d excerpt='%s'\n\n", $firstStats::class, $firstStats->commentCount, $firstStats->excerpt);

echo "=== Ch.12: custom PostQueryInterface (ArticleSearchResult) ===\n";
$result = $articleQuery->search('%Post%');
printf("matched=%d, sql contains 'LIKE'=%s\n", $result->matched, str_contains($result->sql, 'LIKE') ? 'yes' : 'no');
echo "First hit: ", $result->rows[0]->title, "\n\n";

echo "=== Appendix: Multi-statement DML + SELECT PostQuery ===\n";
$created = $articleQuery->createAndGet(
    title: 'Created and fetched',
    body: 'A multi-statement query can return the row created by its first statement.',
    authorName: 'Eve',
);
printf("created article id=%d title='%s' status=%s\n\n", $created->article->id, $created->article->title, $created->article->status);

echo "All chapters executed successfully.\n";
