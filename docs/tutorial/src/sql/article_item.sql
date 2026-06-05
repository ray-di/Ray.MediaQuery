SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE id = :id;
