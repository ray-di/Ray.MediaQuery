---
layout: default
title: BDR Pattern Cookbook
description: "Per-row enrichment with the factory attribute vs. whole-result-set shaping with PostQueryInterface: badges, enums, JOIN grouping, sorting, SPL iterators, and Null Object."
lang: en
permalink: /tutorial/bdr-patterns/
---

# BDR Pattern Cookbook

[日本語 (Japanese)]({{ '/tutorial/bdr-patterns/ja/' | relative_url }}) | [Hands-on Tutorial]({{ '/tutorial/' | relative_url }})

Ray.MediaQuery has two mechanisms for turning SQL results into objects. Which one you pick changes what you can do.

| Mechanism | What it receives | Shape | Use for |
|---|---|---|---|
| **`factory:` attribute** | one call per row, columns as args | 1 row → 1 object | per-row enrichment |
| **`PostQueryInterface`** | the whole result set | N rows → 1 object | aggregation, collections, shaping |

Part 1 uses `factory:`, Part 2 uses `PostQueryInterface`. Confuse them and the code does not run.

---

## Part 1 — `factory:` per-row enrichment

The factory is called **once per row**, with the SELECT columns passed as positional arguments. The return values line up, one per row. Chapter 7's `ArticleStatsFactory` is this.

> The mapping is by **position, not by name** (`PDO::FETCH_FUNC`). The factory signature must line up with the SELECT column list one-to-one, in the same order. A signature shorter than the column list does not "pick" the columns it names — it silently receives the leading ones. Each snippet below therefore assumes the SELECT returns exactly the columns its factory declares.

```php
interface ArticleQueryInterface
{
    /** @return list<Article> */
    #[DbQuery('article_list', factory: ArticleFactory::class)]
    public function list(): array;
}
```

### The `if` vanishes from templates

```twig
{# a familiar sight #}
{% if article.status == 'published' and article.publishedAt <= now %}
    <span class="badge">Live</span>
{% elseif article.status == 'draft' %}
    <span class="badge badge--draft">Draft</span>
{% endif %}
```

Business logic bleeding into the template. Move it to the factory.

```php
final class ArticleFactory
{
    public function factory(int $id, string $title, string $status): Article
    {
        $badge = match($status) {
            'published' => 'badge',
            'draft'     => 'badge badge--draft',
            default     => '',
        };

        return new Article(id: $id, title: $title, status: $status, badge: $badge);
    }
}
```

```twig
{# template only renders #}
<span class="{{ article.badge }}">{{ article.status }}</span>
```

### Receive a string column as an enum

The `status` column is a string. Convert it to a PHP enum in the factory for type-safe comparisons.

```php
public function factory(int $id, string $status): Article
{
    return new Article(id: $id, status: Status::from($status));
}
```

```twig
{# enum comparison, not string comparison #}
{% if article.status == enum('App\\Status::Published') %}...{% endif %}
```

A typo throws at `Status::from()` instead of silently failing in a template. No raw strings scattered around.

### Put display values on the entity

"Show reading time from body length." "Format the price with commas." Compute it in the factory.

```php
public function factory(int $id, string $body, int $priceYen): Article
{
    return new Article(
        id: $id,
        readingMinutes: (int) ceil(mb_strlen($body) / 400),
        priceFormatted: number_format($priceYen) . ' JPY',
    );
}
```

```twig
{{ article.readingMinutes }} min read
{{ article.priceFormatted }}
```

No calculation in the template. The factory is also easy to unit-test in isolation.

### Inject the current time and current user

A factory can receive dependencies through its constructor (same as `age` in chapter 8). Build values that are not in any column from injected services.

```php
final class ArticleFactory
{
    public function __construct(
        private readonly CurrentUserInterface $currentUser,
        private readonly DateTimeInterface $now,
    ) {}

    public function factory(int $id, int $authorId, string $publishedAt): Article
    {
        return new Article(
            id: $id,
            isOwn: $authorId === $this->currentUser->id(),
            isNew: (new DateTimeImmutable($publishedAt)) > DateTimeImmutable::createFromInterface($this->now)->modify('-7 days'),
        );
    }
}
```

```twig
{% if article.isOwn %}<a href="/edit/{{ article.id }}">Edit</a>{% endif %}
{% if article.isNew %}<span class="new">NEW</span>{% endif %}
```

Bind `CurrentUserInterface` in Ray.Di; swap in `FakeCurrentUser` for tests. `DateTimeInterface` is already bound by `MediaQueryModule`.

---

## Part 2 — `PostQueryInterface` shaping the whole result set

Anything that spans rows — grouping, sorting, filtering, emptiness checks — cannot be done in `factory:`, because `factory:` only ever sees one row at a time. When you need the whole result set, use `PostQueryInterface`. Declare the class as the return type; its `fromContext()` receives `$context->rows` (every row). Chapter 12's `ArticleSearchResult` is this.

```php
interface ArticleQueryInterface
{
    #[DbQuery('article_with_comments')]
    public function withComments(): Articles;   // Articles implements PostQueryInterface
}
```

### Assemble flat JOIN rows into a parent-child shape

A JOIN returns flat rows. Grouping comments under each article is tedious in both the template and the controller. Do it in `fromContext()`.

```sql
SELECT a.id, a.title, c.id AS comment_id, c.body AS comment_body
FROM article a
LEFT JOIN comment c ON c.article_id = a.id
ORDER BY a.id
```

```php
final class Articles implements PostQueryInterface
{
    /** @param list<Article> $items */
    public function __construct(public readonly array $items) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $titles = [];
        $comments = [];
        foreach ($context->rows as $row) {
            $id = $row['id'];
            $titles[$id] ??= $row['title'];
            if ($row['comment_id'] !== null) {
                $comments[$id][] = new Comment((int) $row['comment_id'], (string) $row['comment_body']);
            }
        }

        $items = [];
        foreach ($titles as $id => $title) {
            $items[] = new Article((int) $id, (string) $title, $comments[$id] ?? []);
        }

        return new static($items);
    }
}
```

```twig
{% for article in articles.items %}
  <h2>{{ article.title }}</h2>
  {% for comment in article.comments %}<p>{{ comment.body }}</p>{% endfor %}
{% endfor %}
```

One query, nested objects. No N+1, no manual grouping in the controller.

### Sort in `fromContext()`

Some orderings are out of `ORDER BY`'s reach. `ORDER BY name` is lexicographic, so it gives `item1, item10, item2`. Reorder once all rows are in hand.

```php
final class FileList implements PostQueryInterface
{
    /** @param list<File> $files */
    public function __construct(public readonly array $files) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $names = [];
        foreach ($context->rows as $row) {
            $names[(int) $row['id']] = (string) $row['name'];
        }
        natsort($names);                       // item1, item2, item10

        $files = [];
        foreach ($names as $id => $name) {
            $files[] = new File($id, $name);
        }

        return new static($files);
    }
}
```

Business-specific priority (`news → feature → opinion`) lives in the same place — feed `usort()` a `['news' => 0, 'feature' => 1, 'opinion' => 2]` map. The template never thinks about order.

### Filter and limit with SPL iterators

Implementing both `PostQueryInterface` and `IteratorAggregate` lets a collection own a rule like "published only, up to 20." No `{% if %}` repeated in the template.

```php
final class Posts implements PostQueryInterface, IteratorAggregate
{
    /** @param list<Post> $items */
    public function __construct(private readonly array $items) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $items = [];
        foreach ($context->rows as $row) {
            $items[] = new Post((int) $row['id'], (string) $row['title'], (bool) $row['is_published']);
        }

        return new static($items);
    }

    public function getIterator(): Traversable
    {
        return new LimitIterator(
            new CallbackFilterIterator(
                new ArrayIterator($this->items),
                static fn (Post $p) => $p->isPublished,
            ),
            0,
            20,
        );
    }
}
```

```twig
{% for post in posts %}
    {# drafts never reach here, and neither does item 21+ #}
    {{ post.title }}
{% endfor %}
```

`SplPriorityQueue` lets you pin featured posts first, then fall back to date order — same place.

### Null Object — the query always returns a complete entity

When no row is found, a `type: 'row'` query returns `null` — the source of every `{% if profile %}` in a template. Check for emptiness in `fromContext()` and always return a complete entity.

```php
final class UserProfile implements PostQueryInterface
{
    public function __construct(
        public readonly string $displayName,
        public readonly string $avatarUrl,
        public readonly bool $isGuest,
    ) {}

    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? null;
        if ($row === null) {
            return new static('Guest', '/img/guest.png', true);   // placeholder
        }

        return new static((string) $row['name'], (string) $row['avatar_url'], false);
    }
}
```

```twig
{# no existence check; avatarUrl always has a value #}
<img src="{{ profile.avatarUrl }}">
<span>{{ profile.displayName }}</span>
```

The return type is no longer `UserProfile|null` but `UserProfile`. The template never branches.

---

> One thing Part 1 and Part 2 share: **the template only reads properties**. Every decision, calculation, ordering, and emptiness check is finished at the query boundary. The only difference is whether you enrich one row or shape the whole result set.
