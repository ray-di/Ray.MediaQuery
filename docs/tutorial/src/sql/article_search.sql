SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE
    title LIKE :keyword
    OR body LIKE :keyword
ORDER BY id;
