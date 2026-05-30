INSERT INTO article (title, body, author_name, status, created_at)
VALUES (:title, :body, :authorName, :status, :createdAt);

SELECT
    id,
    title,
    body,
    author_name,
    status,
    published_at,
    created_at
FROM article
WHERE id = last_insert_rowid();
